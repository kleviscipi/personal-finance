import Foundation

struct Subcategory: Decodable, Identifiable, Hashable {
    let id: Int
    let categoryId: Int
    let name: String
    let isSystem: Bool
    let order: Int
}
