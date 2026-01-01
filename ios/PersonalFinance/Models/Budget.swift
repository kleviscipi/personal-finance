import Foundation

struct Budget: Decodable, Identifiable, Hashable {
    let id: Int
    let accountId: Int
    let userId: Int?
    let categoryId: Int?
    let subcategoryId: Int?
    let amount: MoneyValue
    let currency: String
    let period: String
    let startDate: String
    let endDate: String?
    let category: Category?
    let progress: BudgetProgress?
}

struct BudgetProgress: Decodable, Hashable {
    let budgetAmount: MoneyValue
    let spent: MoneyValue
    let remaining: MoneyValue
    let percentage: Double
    let isOverspent: Bool
}
