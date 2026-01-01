import Foundation

struct User: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String
    let email: String
}
