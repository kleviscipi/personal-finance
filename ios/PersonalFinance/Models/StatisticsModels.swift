import Foundation

struct StatisticsPayload: Decodable {
    let analytics: StatisticsAnalytics
    let filters: StatisticsFilters
}

struct StatisticsAnalytics: Decodable {
    let monthlySummary: [MonthlySummary]
    let topCategories: [TopCategory]?
    let topSubcategories: [TopSubcategoryItem]?
    let totals: StatisticsTotals?
    let expenseByMonth: [ExpenseByMonth]?
    let medianExpense: Double?
    let missingRates: MissingRates?
    let categoryMix: CategorySeriesGroup?
    let subcategoryMix: CategorySeriesGroup?
    let expenseShare: CategorySeriesGroup?
}

struct StatisticsFilters: Decodable {
    let start: String
    let end: String
}

struct MonthlySummary: Decodable, Identifiable, Hashable {
    let month: String
    let income: MoneyValue
    let expenses: MoneyValue
    let net: MoneyValue

    var id: String { month }
}

struct TopCategory: Decodable, Identifiable, Hashable {
    let categoryId: Int?
    let category: String
    let color: String?
    let total: MoneyValue

    var id: String { "\(categoryId ?? 0)-\(category)" }
}

struct ExpenseByMonth: Decodable, Identifiable, Hashable {
    let month: String
    let total: MoneyValue

    var id: String { month }
}

struct StatisticsTotals: Decodable {
    let income: MoneyValue
    let expenses: MoneyValue
    let transfers: MoneyValue
    let net: MoneyValue
    let openingBalance: MoneyValue?
    let netWithOpening: MoneyValue?
    let netWithOpeningConversions: [TotalBalanceConversion]?
}

struct CategorySeriesGroup: Decodable {
    let months: [String]
    let series: [CategorySeries]
}

struct CategorySeries: Decodable, Identifiable, Hashable {
    let category: String?
    let subcategory: String?
    let label: String?
    let color: String?
    let values: [MoneyValue]

    var id: String { label ?? category ?? subcategory ?? UUID().uuidString }
}
