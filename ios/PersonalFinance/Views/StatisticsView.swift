import Charts
import SwiftUI

struct StatisticsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var payload: StatisticsPayload?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if let totals = payload?.analytics.totals {
                        StatisticsSummaryGrid(totals: totals, formatter: moneyFormatter)
                    }

                    if let summaries = payload?.analytics.monthlySummary, !summaries.isEmpty {
                        StatisticsSection(title: "Monthly Income vs Expenses") {
                            IncomeExpenseLineChart(summary: summaries)
                        }

                        StatisticsSection(title: "Net Cash Flow") {
                            NetCashFlowChart(summary: summaries)
                        }
                    }

                    if let topCategories = payload?.analytics.topCategories, !topCategories.isEmpty {
                        StatisticsSection(title: "Top Categories") {
                            VStack(spacing: 10) {
                                ForEach(topCategories) { category in
                                    HStack {
                                        Circle()
                                            .fill(Color(hex: category.color ?? "") ?? Color.gray.opacity(0.3))
                                            .frame(width: 8, height: 8)
                                        Text(category.category)
                                            .font(.caption)
                                        Spacer()
                                        Text(formatMoney(category.total))
                                            .font(.caption)
                                    }
                                }
                            }
                            .cardStyle()
                        }
                    }

                    if let topSubcategories = payload?.analytics.topSubcategories, !topSubcategories.isEmpty {
                        StatisticsSection(title: "Top Subcategories") {
                            VStack(spacing: 10) {
                                ForEach(topSubcategories) { sub in
                                    HStack {
                                        Circle()
                                            .fill(Color(hex: sub.color ?? "") ?? Color.gray.opacity(0.3))
                                            .frame(width: 8, height: 8)
                                        Text(sub.label ?? sub.subcategory ?? "")
                                            .font(.caption)
                                        Spacer()
                                        Text(formatMoney(sub.total))
                                            .font(.caption)
                                    }
                                }
                            }
                            .cardStyle()
                        }
                    }

                    if let summaries = payload?.analytics.monthlySummary, !summaries.isEmpty {
                        StatisticsSection(title: "Monthly Summary") {
                            VStack(spacing: 10) {
                                ForEach(summaries) { summary in
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(formatMonth(summary.month))
                                            .font(.subheadline.bold())
                                        Text("Income: \(formatMoney(summary.income))")
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                        Text("Expenses: \(formatMoney(summary.expenses))")
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                        Text("Net: \(formatMoney(summary.net))")
                                            .font(.caption)
                                            .foregroundStyle(.secondary)
                                    }
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
                    ProgressView("Loading statistics...")
                }
            }
            .navigationTitle("Statistics")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await loadStatistics() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .task {
                await loadStatistics()
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

    private func loadStatistics() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            payload = try await appState.fetchStatistics()
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

    private func formatMoney(_ value: MoneyValue) -> String {
        moneyFormatter.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }

    private func formatMonth(_ value: String) -> String {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM"
        let output = DateFormatter()
        output.dateFormat = "MMM yyyy"
        if let date = formatter.date(from: value) {
            return output.string(from: date)
        }
        return value
    }
}

private struct StatisticsSection<Content: View>: View {
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

private struct StatisticsSummaryGrid: View {
    let totals: StatisticsTotals
    let formatter: NumberFormatter

    private let columns = [
        GridItem(.flexible()),
        GridItem(.flexible())
    ]

    var body: some View {
        LazyVGrid(columns: columns, spacing: 12) {
            SummaryCard(title: "Income", value: formatMoney(totals.income))
            SummaryCard(title: "Expenses", value: formatMoney(totals.expenses))
            SummaryCard(title: "Transfers", value: formatMoney(totals.transfers))
            SummaryCard(title: "Net", value: formatMoney(totals.net))
        }
    }

    private func formatMoney(_ value: MoneyValue) -> String {
        formatter.string(from: NSNumber(value: value.doubleValue)) ?? value.raw
    }
}

private struct IncomeExpenseLineChart: View {
    let summary: [MonthlySummary]

    var body: some View {
        Chart {
            ForEach(summary) { row in
                LineMark(
                    x: .value("Month", row.month),
                    y: .value("Income", row.income.doubleValue)
                )
                .foregroundStyle(Color.green)

                LineMark(
                    x: .value("Month", row.month),
                    y: .value("Expenses", row.expenses.doubleValue)
                )
                .foregroundStyle(Color.red)
            }
        }
        .frame(height: 220)
        .cardStyle()
    }
}

private struct NetCashFlowChart: View {
    let summary: [MonthlySummary]

    var body: some View {
        Chart(summary) { row in
            BarMark(
                x: .value("Month", row.month),
                y: .value("Net", row.net.doubleValue)
            )
            .foregroundStyle(row.net.doubleValue >= 0 ? Color.green : Color.red)
        }
        .frame(height: 220)
        .cardStyle()
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
