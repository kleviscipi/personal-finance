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
    let currentMonthSavings: MonthSavings?
    let totalBalance: MoneyValue?
    let totalBalanceOpening: MoneyValue?
    let totalBalanceNet: MoneyValue?
    let totalBalanceConversions: [TotalBalanceConversion]?
    let budgetUsage: [BudgetUsageItem]?
    let budgetVariance: [BudgetVarianceItem]?
    let expensesByCategory: [ExpenseByCategory]?
    let monthlySummary: [MonthlySummary]?
    let balanceHistory: [BalanceHistory]?
    let topCategories: [TopCategoryItem]?
    let topSubcategories: [TopSubcategoryItem]?
    let savingsRate: SavingsRate?
    let forecast: ForecastSummary?
    let categorySpikes: [CategorySpike]?
    let missingRates: MissingRates?
}

struct MonthSavings: Decodable {
    let amount: MoneyValue
    let rate: Double
    let income: MoneyValue
    let expenses: MoneyValue
}

struct TotalBalanceConversion: Decodable, Hashable {
    let currency: String
    let amount: MoneyValue
    let rate: MoneyValue?
    let rateDate: String?
}

struct BudgetUsageItem: Decodable, Hashable, Identifiable {
    let id: Int
    let userId: Int?
    let category: String?
    let userName: String?
    let userEmail: String?
    let budget: MoneyValue
    let spent: MoneyValue
    let remaining: MoneyValue
}

struct BudgetVarianceItem: Decodable, Hashable, Identifiable {
    let id: Int
    let userId: Int?
    let category: String?
    let color: String?
    let userName: String?
    let userEmail: String?
    let budget: MoneyValue
    let spent: MoneyValue
    let variance: MoneyValue
}

struct ExpenseByCategory: Decodable, Hashable, Identifiable {
    let category: String
    let icon: String?
    let color: String?
    let total: MoneyValue

    var id: String { category }
}

struct BalanceHistory: Decodable, Hashable, Identifiable {
    let month: String
    let income: MoneyValue
    let expenses: MoneyValue
    let savings: MoneyValue
    let balance: MoneyValue

    var id: String { month }
}

struct TopCategoryItem: Decodable, Hashable, Identifiable {
    let category: String
    let color: String?
    let total: MoneyValue
    let percentage: Double?

    var id: String { category }
}

struct TopSubcategoryItem: Decodable, Hashable, Identifiable {
    let subcategory: String?
    let category: String?
    let label: String?
    let color: String?
    let total: MoneyValue
    let percentage: Double?

    var id: String { label ?? subcategory ?? UUID().uuidString }
}

struct SavingsRate: Decodable {
    let income: MoneyValue?
    let expenses: MoneyValue?
    let rate: Double
}

struct ForecastSummary: Decodable {
    let last30Days: ForecastWindow?
    let forecast30: ForecastWindow?
    let forecast90: ForecastWindow?
}

struct ForecastWindow: Decodable {
    let income: MoneyValue?
    let expenses: MoneyValue?
    let net: MoneyValue?
}

struct CategorySpike: Decodable, Hashable, Identifiable {
    let category: String
    let color: String?
    let recentTotal: MoneyValue?
    let baseline: MoneyValue?
    let deltaPercent: Double?

    var id: String { category }
}

struct MissingRates: Decodable {
    let count: Int
    let currencies: [String]?
    let firstDate: String?
    let lastDate: String?
}
