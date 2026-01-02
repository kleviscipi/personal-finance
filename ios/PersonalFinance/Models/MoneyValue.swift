import Foundation

struct MoneyValue: Decodable, Hashable {
    let raw: String

    init(raw: String) {
        self.raw = raw
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let string = try? container.decode(String.self) {
            self.raw = string
        } else if let intValue = try? container.decode(Int.self) {
            self.raw = String(intValue)
        } else if let doubleValue = try? container.decode(Double.self) {
            self.raw = String(doubleValue)
        } else {
            self.raw = "0"
        }
    }

    var doubleValue: Double {
        Double(raw) ?? 0
    }
}
