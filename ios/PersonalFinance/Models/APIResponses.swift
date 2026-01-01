import Foundation

struct APIResponse<T: Decodable>: Decodable {
    let data: T
}

struct APICollectionResponse<T: Decodable>: Decodable {
    let data: [T]
    let meta: PaginationMeta?
    let links: PaginationLinks?
}

struct PaginationMeta: Decodable {
    let currentPage: Int?
    let perPage: Int?
    let total: Int?
    let lastPage: Int?
}

struct PaginationLinks: Decodable {
    let first: String?
    let last: String?
    let prev: String?
    let next: String?
}
