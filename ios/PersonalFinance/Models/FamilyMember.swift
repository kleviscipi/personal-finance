import Foundation

struct FamilyMember: Decodable, Identifiable {
    let id: Int
    let name: String
    let email: String
    let role: String
    let isActive: Bool
    let joinedAt: String?
}

struct InviteMemberRequest: Encodable {
    let email: String
    let role: String
}

struct UpdateMemberRequest: Encodable {
    let role: String
    let isActive: Bool
}
