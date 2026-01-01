import Foundation

struct Transaction: Decodable, Identifiable, Hashable {
    let id: Int
    let accountId: Int
    let createdBy: Int
    let type: String
    let amount: String
    let currency: String
    let date: String
    let categoryId: Int?
    let subcategoryId: Int?
    let description: String?
    let paymentMethod: String?
    let metadata: [String: JSONValue]?
    let category: Category?
    let subcategory: Subcategory?
    let creator: User?
    let tags: [Tag]?
}
