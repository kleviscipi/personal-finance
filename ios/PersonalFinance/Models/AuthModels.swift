import Foundation

struct AuthPayload: Decodable {
    let user: User
    let token: String
    let tokenType: String
}

struct MePayload: Decodable {
    let user: User
    let accounts: [Account]
}
