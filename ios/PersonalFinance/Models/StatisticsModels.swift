import Foundation

struct StatisticsPayload: Decodable {
    let analytics: StatisticsAnalytics
    let filters: StatisticsFilters
}

struct StatisticsAnalytics: Decodable {
    let monthlySummary: [MonthlySummary]
    let topCategories: [TopCategory]?
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
