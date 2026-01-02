import SwiftUI

struct BudgetsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var budgets: [Budget] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewBudget = false
    @State private var selectedBudget: Budget?
    @State private var budgetToDelete: Budget?
    @State private var showingDeleteAlert = false

    var body: some View {
        NavigationStack {
            List {
                ForEach(budgets) { budget in
                    BudgetRow(budget: budget)
                        .cardStyle()
                        .listRowSeparator(.hidden)
                        .listRowBackground(Color.clear)
                        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                            Button(role: .destructive) {
                                budgetToDelete = budget
                                showingDeleteAlert = true
                            } label: {
                                Label("Delete", systemImage: "trash")
                            }
                            
                            Button {
                                selectedBudget = budget
                            } label: {
                                Label("Edit", systemImage: "pencil")
                            }
                            .tint(.blue)
                        }
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading budgets...")
                } else if budgets.isEmpty {
                    ContentUnavailableView(
                        "No Budgets",
                        systemImage: "chart.pie",
                        description: Text("Create a budget to track your spending")
                    )
                }
            }
            .navigationTitle("Budgets")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button {
                        Task { await loadBudgets() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        showingNewBudget = true
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .task {
                await loadBudgets()
            }
            .listStyle(.plain)
            .sheet(isPresented: $showingNewBudget) {
                NewBudgetView { newBudget in
                    budgets.insert(newBudget, at: 0)
                }
            }
            .sheet(item: $selectedBudget) { budget in
                EditBudgetView(budget: budget) { updated in
                    if let index = budgets.firstIndex(where: { $0.id == budget.id }) {
                        budgets[index] = updated
                    }
                }
            }
            .alert("Delete Budget", isPresented: $showingDeleteAlert, presenting: budgetToDelete) { budget in
                Button("Cancel", role: .cancel) {
                    budgetToDelete = nil
                }
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteBudget(budget)
                    }
                }
            } message: { budget in
                Text("Are you sure you want to delete this budget? This action cannot be undone.")
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

    private func loadBudgets() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            budgets = try await appState.fetchBudgets()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func deleteBudget(_ budget: Budget) async {
        do {
            try await appState.deleteBudget(budget.id)
            budgets.removeAll { $0.id == budget.id }
            budgetToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct BudgetRow: View {
    let budget: Budget

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            HStack {
                Circle()
                    .fill(Color(hex: budget.category?.color ?? "") ?? Color.gray.opacity(0.3))
                    .frame(width: 10, height: 10)
                Text(budget.category?.name ?? "General Budget")
                    .font(.headline)
            }
            Text("\(budget.amount.raw) \(budget.currency)")
                .font(.subheadline)
                .foregroundStyle(.secondary)
            if let progress = budget.progress {
                ProgressView(value: progress.percentage / 100)
                Text("Spent: \(progress.spent.raw)")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(.vertical, 4)
    }
}
