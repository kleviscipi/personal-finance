import Foundation

struct DashboardPayload: Decodable {
    let analytics: DashboardAnalytics
    let recentTransactions: [Transaction]
    let savingsGoals: [SavingsGoal]
}

struct DashboardAnalytics: Decodable {
    let currentMonthExpenses: MoneyValue?
    let currentMonthIncome: MoneyValue?
    let currentMonthTransactionCount: Int?
    let netCashFlow: MoneyValue?
    let totalBalanceNet: MoneyValue?
}
