import SwiftUI

struct DashboardView: View {
    @EnvironmentObject private var appState: AppState
    @State private var payload: DashboardPayload?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if let analytics = payload?.analytics {
                        DashboardSummaryGrid(analytics: analytics)
                    }

                    if let transactions = payload?.recentTransactions {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Recent Transactions")
                                .font(.headline)
                            ForEach(transactions.prefix(5)) { transaction in
                                TransactionRow(transaction: transaction)
                                    .cardStyle()
                            }
                        }
                    }

                    if let goals = payload?.savingsGoals, !goals.isEmpty {
                        VStack(alignment: .leading, spacing: 8) {
                            Text("Savings Goals")
                                .font(.headline)
                            ForEach(goals) { goal in
                                SavingsGoalRow(goal: goal)
                                    .cardStyle()
                            }
                        }
                    }
                }
                .padding(16)
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading dashboard...")
                }
            }
            .navigationTitle("Dashboard")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await loadDashboard() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .task {
                await loadDashboard()
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

    private func loadDashboard() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            payload = try await appState.fetchDashboard()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct DashboardSummaryGrid: View {
    let analytics: DashboardAnalytics

    private let columns = [
        GridItem(.flexible()),
        GridItem(.flexible())
    ]

    var body: some View {
        LazyVGrid(columns: columns, spacing: 12) {
            SummaryCard(title: "Income", value: analytics.currentMonthIncome?.raw ?? "0")
            SummaryCard(title: "Expenses", value: analytics.currentMonthExpenses?.raw ?? "0")
            SummaryCard(title: "Net Cash Flow", value: analytics.netCashFlow?.raw ?? "0")
            SummaryCard(title: "Transactions", value: String(analytics.currentMonthTransactionCount ?? 0))
        }
    }
}

private struct SummaryCard: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.title3.bold())
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .cardStyle()
    }
}

private struct SavingsGoalRow: View {
    let goal: SavingsGoal

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(goal.name)
                .font(.subheadline.bold())
            Text("Target: \(goal.targetAmount.raw) \(goal.currency)")
                .font(.caption)
                .foregroundStyle(.secondary)
            if let progress = goal.progress {
                ProgressView(value: progress.percentage / 100)
            }
        }
        .padding(.vertical, 4)
    }
}
