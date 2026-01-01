import Foundation

struct Category: Decodable, Identifiable, Hashable {
    let id: Int
    let accountId: Int
    let name: String
    let icon: String?
    let color: String?
    let type: String
    let isSystem: Bool
    let order: Int
    let subcategories: [Subcategory]?
}
