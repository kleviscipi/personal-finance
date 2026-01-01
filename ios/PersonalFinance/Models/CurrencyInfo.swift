import Foundation

struct CurrencyInfo: Decodable, Identifiable, Hashable {
    let name: String
    let symbol: String
    let code: String

    var id: String { code }
}
