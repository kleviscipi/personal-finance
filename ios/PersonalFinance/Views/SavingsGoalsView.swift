import SwiftUI

struct SavingsGoalsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var goals: [SavingsGoal] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewGoal = false
    @State private var selectedGoal: SavingsGoal?
    @State private var goalToDelete: SavingsGoal?
    @State private var showingDeleteAlert = false

    var body: some View {
        NavigationStack {
            List {
                ForEach(goals) { goal in
                    SavingsGoalRow(goal: goal)
                        .cardStyle()
                        .listRowSeparator(.hidden)
                        .listRowBackground(Color.clear)
                        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                            Button(role: .destructive) {
                                goalToDelete = goal
                                showingDeleteAlert = true
                            } label: {
                                Label("Delete", systemImage: "trash")
                            }
                            
                            Button {
                                selectedGoal = goal
                            } label: {
                                Label("Edit", systemImage: "pencil")
                            }
                            .tint(.blue)
                        }
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading goals...")
                } else if goals.isEmpty {
                    ContentUnavailableView(
                        "No Savings Goals",
                        systemImage: "target",
                        description: Text("Create a goal to start tracking your savings progress")
                    )
                }
            }
            .navigationTitle("Savings Goals")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button {
                        Task { await loadGoals() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        showingNewGoal = true
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .task {
                await loadGoals()
            }
            .listStyle(.plain)
            .sheet(isPresented: $showingNewGoal) {
                NewSavingsGoalView { newGoal in
                    goals.insert(newGoal, at: 0)
                }
            }
            .sheet(item: $selectedGoal) { goal in
                EditSavingsGoalView(goal: goal) { updatedGoal in
                    if let index = goals.firstIndex(where: { $0.id == goal.id }) {
                        goals[index] = updatedGoal
                    }
                }
            }
            .alert("Delete Goal", isPresented: $showingDeleteAlert, presenting: goalToDelete) { goal in
                Button("Cancel", role: .cancel) {
                    goalToDelete = nil
                }
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteGoal(goal)
                    }
                }
            } message: { goal in
                Text("Are you sure you want to delete '\(goal.name)'? This action cannot be undone.")
            }
            .alert("Error", isPresented: Binding(
                get: { errorMessage != nil },
                set: { if !$0 { errorMessage = nil } }
            )) {
                Button("OK", role: .cancel) {}
            } message: {
                Text(errorMessage ?? "")
            }
        }
    }

    private func loadGoals() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            goals = try await appState.fetchSavingsGoals()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func deleteGoal(_ goal: SavingsGoal) async {
        do {
            try await appState.deleteSavingsGoal(goal.id)
            goals.removeAll { $0.id == goal.id }
            goalToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct SavingsGoalRow: View {
    let goal: SavingsGoal

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text(goal.name)
                    .font(.headline)
                Spacer()
                Text(goal.currency)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            
            if let progress = goal.progress {
                VStack(alignment: .leading, spacing: 8) {
                    HStack {
                        Text("\(progress.currentAmount.raw)")
                            .font(.title3)
                            .fontWeight(.semibold)
                        Text("of \(goal.targetAmount.raw)")
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                    
                    ProgressView(value: min(progress.percentage, 100) / 100)
                        .tint(progressColor(progress.percentage))
                    
                    HStack {
                        Text("\(Int(progress.percentage))% complete")
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Spacer()
                        if !progress.isComplete {
                            Text("Remaining: \(progress.remaining.raw)")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                        } else {
                            Text("✓ Complete")
                                .font(.caption)
                                .foregroundStyle(.green)
                        }
                    }
                }
            }
            
            HStack {
                Label(formatDate(goal.startDate), systemImage: "calendar")
                Spacer()
                Label(formatDate(goal.targetDate), systemImage: "flag.checkered")
            }
            .font(.caption)
            .foregroundStyle(.secondary)
            
            Text(trackingModeLabel(goal.trackingMode))
                .font(.caption)
                .padding(.horizontal, 8)
                .padding(.vertical, 4)
                .background(Color.blue.opacity(0.1))
                .foregroundStyle(.blue)
                .cornerRadius(4)
        }
        .padding(.vertical, 4)
    }
    
    private func progressColor(_ percentage: Double) -> Color {
        if percentage >= 100 {
            return .green
        } else if percentage >= 60 {
            return .blue
        } else {
            return .orange
        }
    }
    
    private func formatDate(_ dateString: String) -> String {
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withFullDate, .withDashSeparatorInDate]
        
        guard let date = formatter.date(from: dateString) else {
            return dateString
        }
        
        let displayFormatter = DateFormatter()
        displayFormatter.dateStyle = .medium
        return displayFormatter.string(from: date)
    }
    
    private func trackingModeLabel(_ mode: String) -> String {
        switch mode {
        case "manual":
            return "Manual tracking"
        case "subcategory":
            return "Subcategory tracking"
        case "category":
            return "Category tracking"
        case "net_savings":
            return "Net savings tracking"
        default:
            return mode.capitalized
        }
    }
}
