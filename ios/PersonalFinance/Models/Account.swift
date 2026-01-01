import Foundation

struct Account: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String
    let baseCurrency: String
    let description: String?
    let isActive: Bool
    let memberRole: String?
    let memberIsActive: Bool?
}
