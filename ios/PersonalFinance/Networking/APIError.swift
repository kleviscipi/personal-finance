import Foundation

struct APIErrorResponse: Decodable {
    let message: String?
    let errors: [String: [String]]?
}

enum APIError: LocalizedError {
    case invalidURL
    case invalidResponse
    case server(String)
    case decoding(String)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid URL."
        case .invalidResponse:
            return "Unexpected server response."
        case .server(let message):
            return message
        case .decoding(let message):
            return message
        }
    }
}

struct AnyEncodable: Encodable {
    private let encodeBlock: (Encoder) throws -> Void

    init<T: Encodable>(_ wrapped: T) {
        self.encodeBlock = wrapped.encode
    }

    func encode(to encoder: Encoder) throws {
        try encodeBlock(encoder)
    }
}
