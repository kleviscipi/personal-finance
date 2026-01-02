import Charts
import SwiftUI

struct DashboardView: View {
    @EnvironmentObject private var appState: AppState
    @State private var payload: DashboardPayload?
    @State private var recentTransactions: [Transaction] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewTransaction = false

    var body: some View {
        NavigationStack {
            ZStack(alignment: .bottomTrailing) {
                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                        if let analytics = payload?.analytics {
                            DashboardSummaryGrid(analytics: analytics)

                            if let expenses = analytics.expensesByCategory, !expenses.isEmpty {
                                DashboardSection(title: "Expenses by Category") {
                                    ExpenseDonutChart(expenses: expenses)
                                }
                            }

                            if let usage = analytics.budgetUsage, !usage.isEmpty {
                                DashboardSection(title: "Budget Usage") {
                                    VStack(spacing: 12) {
                                        ForEach(usage.prefix(5)) { item in
                                            BudgetUsageRow(item: item, formatter: moneyFormatter)
                                        }
                                    }
                                }
                            }

                            if let balanceHistory = analytics.balanceHistory, !balanceHistory.isEmpty {
                                DashboardSection(title: "Balance & Savings Trend (12 Months)") {
                                    BalanceTrendChart(history: balanceHistory)
                                }
                            }

                            if let monthlySummary = analytics.monthlySummary, !monthlySummary.isEmpty {
                                DashboardSection(title: "Cash Flow Trend") {
                                    CashFlowChart(summary: monthlySummary)
                                }

                                DashboardSection(title: "Income vs Expenses") {
                                    IncomeExpenseChart(summary: monthlySummary)
                                }
                            }

                            HStack(alignment: .top, spacing: 12) {
                                if let topCategories = analytics.topCategories {
                                    DashboardCompactList(
                                        title: "Top Categories (30 Days)",
                                        items: topCategories.map { ($0.category, $0.color, formatMoney($0.total), $0.percentage) }
                                    )
                                }
                                if let topSubcategories = analytics.topSubcategories {
                                    DashboardCompactList(
                                        title: "Top Subcategories (30 Days)",
                                        items: topSubcategories.map { ($0.label ?? $0.subcategory ?? "", $0.color, formatMoney($0.total), $0.percentage) }
                                    )
                                }
                            }

                            if let variance = analytics.budgetVariance, !variance.isEmpty {
                                DashboardSection(title: "Budget Variance") {
                                    VStack(spacing: 10) {
                                        ForEach(variance) { item in
                                            BudgetVarianceRow(item: item, formatter: moneyFormatter)
                                        }
                                    }
                                }
                            }

                            HStack(alignment: .top, spacing: 12) {
                                SavingsRateCard(rate: analytics.savingsRate)
                                ForecastCard(forecast: analytics.forecast, currencyCode: appState.activeAccount?.baseCurrency ?? "USD")
                                CategorySpikeCard(spikes: analytics.categorySpikes ?? [])
                            }

                            if let monthlySummary = analytics.monthlySummary, !monthlySummary.isEmpty {
                                DashboardSection(title: "Balance Change (Net by Month)") {
                                    BalanceChangeGrid(summary: monthlySummary, currencyCode: appState.activeAccount?.baseCurrency ?? "USD")
                                }
                            }
                        }

                        if let goals = payload?.savingsGoals, !goals.isEmpty {
                            DashboardSection(title: "Savings Goals") {
                                VStack(spacing: 12) {
                                    ForEach(goals) { goal in
                                        SavingsGoalRow(goal: goal, formatter: moneyFormatter)
                                            .cardStyle()
                                    }
                                }
                            }
                        }

                        if !recentTransactions.isEmpty {
                            DashboardSection(title: "Recent Transactions") {
                                VStack(spacing: 12) {
                                    ForEach(recentTransactions.prefix(8)) { transaction in
                                        TransactionRow(transaction: transaction)
                                            .cardStyle()
                                    }
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

                Button {
                    showingNewTransaction = true
                } label: {
                    Image(systemName: "plus")
                        .font(.system(size: 20, weight: .bold))
                        .foregroundStyle(.white)
                        .frame(width: 56, height: 56)
                        .background(Color(red: 0.32, green: 0.62, blue: 0.96))
                        .clipShape(Circle())
                        .shadow(color: Color.black.opacity(0.25), radius: 12, x: 0, y: 6)
                }
                .padding(.trailing, 20)
                .padding(.bottom, 20)
            }
            .sheet(isPresented: $showingNewTransaction) {
                NewTransactionView { newTransaction in
                    recentTransactions.insert(newTransaction, at: 0)
                }
            }
        }
    }

    private func loadDashboard() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            payload = try await appState.fetchDashboard()
            recentTransactions = payload?.recentTransactions ?? []
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private var moneyFormatter: NumberFormatter {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = appState.activeAccount?.baseCurrency ?? "USD"
        formatter.minimumFractionDigits = 2
        formatter.maximumFractionDigits = 2
        return formatter
    }

    private func formatMoney(_ value: MoneyValue?) -> String {
        let amount = value?.doubleValue ?? 0
        return moneyFormatter.string(from: NSNumber(value: amount)) ?? value?.raw ?? "0"
    }

}

private struct DashboardSummaryGrid: View {
    let analytics: DashboardAnalytics
    @EnvironmentObject private var appState: AppState

    private let columns = [
        GridItem(.flexible()),
        GridItem(.flexible())
    ]

    var body: some View {
        LazyVGrid(columns: columns, spacing: 12) {
            SummaryCard(title: "Monthly Income", value: formatMoney(analytics.currentMonthIncome))
            SummaryCard(title: "Monthly Expenses", value: formatMoney(analytics.currentMonthExpenses))
            SummaryCard(title: "Monthly Savings", value: formatMoney(analytics.currentMonthSavings?.amount))
            SummaryCard(title: "Total Balance", value: formatMoney(analytics.totalBalance))
            SummaryCard(title: "Active Budgets", value: String(analytics.budgetUsage?.count ?? 0))
            SummaryCard(title: "Monthly Transactions", value: String(analytics.currentMonthTransactionCount ?? 0))
        }
    }

    private var moneyFormatter: NumberFormatter {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = appState.activeAccount?.baseCurrency ?? "USD"
        formatter.minimumFractionDigits = 2
        formatter.maximumFractionDigits = 2
        return formatter
    }

    private func formatMoney(_ value: MoneyValue?) -> String {
        let amount = value?.doubleValue ?? 0
        return moneyFormatter.string(from: NSNumber(value: amount)) ?? value?.raw ?? "0"
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
    let formatter: NumberFormatter

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(goal.name)
                .font(.subheadline.bold())
            Text("Target: \(formatMoney(goal.targetAmount))")
                .font(.caption)
                .foregroundStyle(.secondary)
            if let progress = goal.progress {
                ProgressView(value: progress.percentage / 100)
            }
        }
        .padding(.vertical, 4)
    }

    private func formatMoney(_ value: MoneyValue) -> String {
        let local = NumberFormatter()
        local.numberStyle = .currency
        local.currencyCode = goal.currency
        local.minimumFractionDigits = 2
        local.maximumFractionDigits = 2
        return local.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }
}

private struct DashboardSection<Content: View>: View {
    let title: String
    @ViewBuilder let content: Content

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.headline)
            content
        }
    }
}

private struct ExpenseDonutChart: View {
    let expenses: [ExpenseByCategory]

    var body: some View {
        Chart(expenses) { item in
            SectorMark(
                angle: .value("Amount", item.total.doubleValue),
                innerRadius: .ratio(0.6)
            )
            .foregroundStyle(Color(hex: item.color ?? "") ?? .blue)
        }
        .frame(height: 220)
    }
}

private struct BudgetUsageRow: View {
    let item: BudgetUsageItem
    let formatter: NumberFormatter

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(item.category ?? "All categories")
                    .font(.subheadline.weight(.semibold))
                Spacer()
                Text("\(formatMoney(item.spent)) / \(formatMoney(item.budget))")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            ProgressView(value: usagePercent)
                .tint(usageColor)
        }
        .cardStyle()
    }

    private func formatMoney(_ value: MoneyValue) -> String {
        formatter.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }

    private var usagePercent: Double {
        let budget = item.budget.doubleValue
        guard budget > 0 else { return 0 }
        return min(item.spent.doubleValue / budget, 1)
    }

    private var usageColor: Color {
        let budget = item.budget.doubleValue
        guard budget > 0 else { return .green }
        let ratio = item.spent.doubleValue / budget
        if ratio > 1 { return .red }
        if ratio > 0.8 { return .orange }
        return .green
    }
}

private struct BalanceTrendChart: View {
    let history: [BalanceHistory]

    var body: some View {
        Chart {
            ForEach(history) { row in
                LineMark(
                    x: .value("Month", row.month),
                    y: .value("Balance", row.balance.doubleValue)
                )
                .foregroundStyle(Color.indigo)

                LineMark(
                    x: .value("Month", row.month),
                    y: .value("Savings", row.savings.doubleValue)
                )
                .foregroundStyle(Color.green)
            }
        }
        .frame(height: 240)
    }
}

private struct CashFlowChart: View {
    let summary: [MonthlySummary]

    var body: some View {
        Chart(summary) { row in
            LineMark(
                x: .value("Month", row.month),
                y: .value("Net", row.net.doubleValue)
            )
            .foregroundStyle(Color.blue)
        }
        .frame(height: 220)
    }
}

private struct IncomeExpenseChart: View {
    let summary: [MonthlySummary]

    var body: some View {
        Chart(summary) { row in
            BarMark(
                x: .value("Month", row.month),
                y: .value("Income", row.income.doubleValue)
            )
            .foregroundStyle(Color.green)
            BarMark(
                x: .value("Month", row.month),
                y: .value("Expenses", row.expenses.doubleValue)
            )
            .foregroundStyle(Color.red)
        }
        .frame(height: 220)
    }
}

private struct DashboardCompactList: View {
    let title: String
    let items: [(String, String?, String, Double?)]

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(title)
                .font(.headline)
            ForEach(Array(items.prefix(6).enumerated()), id: \.offset) { _, item in
                HStack {
                    Circle()
                        .fill(Color(hex: item.1 ?? "") ?? Color.gray.opacity(0.3))
                        .frame(width: 8, height: 8)
                    Text(item.0)
                        .font(.caption)
                    Spacer()
                    Text(item.2)
                        .font(.caption)
                    if let pct = item.3 {
                        Text("(\(String(format: "%.1f", pct))%)")
                            .font(.caption2)
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .cardStyle()
    }
}

private struct BudgetVarianceRow: View {
    let item: BudgetVarianceItem
    let formatter: NumberFormatter

    var body: some View {
        HStack {
            Circle()
                .fill(Color(hex: item.color ?? "") ?? Color.gray.opacity(0.3))
                .frame(width: 8, height: 8)
            VStack(alignment: .leading, spacing: 2) {
                Text(item.category ?? "All categories")
                    .font(.subheadline)
                Text(item.userName ?? item.userEmail ?? item.userId.map { "User \($0)" } ?? "Account-wide")
                    .font(.caption2)
                    .foregroundStyle(.secondary)
            }
            Spacer()
            Text(formatMoney(item.variance))
                .font(.caption)
                .foregroundStyle(item.variance.doubleValue > 0 ? .red : .green)
        }
        .cardStyle()
    }

    private func formatMoney(_ value: MoneyValue) -> String {
        formatter.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }
}

private struct SavingsRateCard: View {
    let rate: SavingsRate?

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text("Savings Rate")
                .font(.headline)
            Text("\(String(format: "%.1f", rate?.rate ?? 0))%")
                .font(.title3.bold())
            Text("Income vs expenses this month")
                .font(.caption)
                .foregroundStyle(.secondary)
        }
        .cardStyle()
    }
}

private struct ForecastCard: View {
    let forecast: ForecastSummary?
    let currencyCode: String

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("30 / 90 Day Forecast")
                .font(.headline)
            HStack {
                Text("30 days")
                Spacer()
                Text(formatMoney(forecast?.forecast30?.net))
                    .font(.caption)
            }
            HStack {
                Text("90 days")
                Spacer()
                Text(formatMoney(forecast?.forecast90?.net))
                    .font(.caption)
            }
        }
        .cardStyle()
    }

    private var formatter: NumberFormatter {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = currencyCode
        formatter.minimumFractionDigits = 2
        formatter.maximumFractionDigits = 2
        return formatter
    }

    private func formatMoney(_ value: MoneyValue?) -> String {
        let amount = value?.doubleValue ?? 0
        return formatter.string(from: NSNumber(value: amount)) ?? value?.raw ?? "0"
    }
}

private struct CategorySpikeCard: View {
    let spikes: [CategorySpike]

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Category Spikes")
                .font(.headline)
            if spikes.isEmpty {
                Text("No spikes detected.")
                    .font(.caption)
                    .foregroundStyle(.secondary)
            } else {
                ForEach(spikes.prefix(5)) { spike in
                    HStack {
                        Text(spike.category)
                            .font(.caption)
                        Spacer()
                        Text("+\(String(format: "%.1f", spike.deltaPercent ?? 0))%")
                            .font(.caption)
                            .foregroundStyle(.red)
                    }
                }
            }
        }
        .cardStyle()
    }
}

private struct BalanceChangeGrid: View {
    let summary: [MonthlySummary]
    let currencyCode: String

    private let columns = [
        GridItem(.flexible()),
        GridItem(.flexible()),
        GridItem(.flexible())
    ]

    var body: some View {
        LazyVGrid(columns: columns, spacing: 10) {
            ForEach(summary) { row in
                VStack(alignment: .leading, spacing: 4) {
                    Text(formatMonth(row.month))
                        .font(.caption2)
                        .foregroundStyle(.secondary)
                    Text(formatMoney(row.net))
                        .font(.caption)
                        .foregroundStyle(row.net.doubleValue >= 0 ? .green : .red)
                }
                .cardStyle()
            }
        }
    }

    private func formatMonth(_ value: String) -> String {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM"
        let output = DateFormatter()
        output.dateFormat = "MMM"
        if let date = formatter.date(from: value) {
            return output.string(from: date)
        }
        return value
    }

    private func formatMoney(_ value: MoneyValue) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = currencyCode
        formatter.minimumFractionDigits = 0
        formatter.maximumFractionDigits = 0
        return formatter.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }
}
