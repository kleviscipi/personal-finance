import Foundation

struct SavingsGoal: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String
    let targetAmount: MoneyValue
    let initialAmount: MoneyValue?
    let currency: String
    let trackingMode: String?
    let startDate: String?
    let targetDate: String?
    let progress: SavingsGoalProgress?
}

struct SavingsGoalProgress: Decodable, Hashable {
    let currentAmount: MoneyValue
    let contributed: MoneyValue
    let remaining: MoneyValue
    let percentage: Double
    let isComplete: Bool
}
