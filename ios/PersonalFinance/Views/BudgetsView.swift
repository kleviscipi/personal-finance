import SwiftUI

struct BudgetsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var budgets: [Budget] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewBudget = false

    var body: some View {
        NavigationStack {
            List {
                ForEach(budgets) { budget in
                    BudgetRow(budget: budget)
                        .cardStyle()
                        .listRowSeparator(.hidden)
                        .listRowBackground(Color.clear)
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading budgets...")
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
