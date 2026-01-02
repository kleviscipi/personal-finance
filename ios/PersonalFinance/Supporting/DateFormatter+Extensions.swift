import Foundation

extension DateFormatter {
    /// ISO8601 date formatter for API dates (YYYY-MM-DD format)
    static let apiDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        return formatter
    }()
    
    /// User-friendly date formatter for display
    static let displayDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        formatter.timeStyle = .none
        return formatter
    }()
}

extension ISO8601DateFormatter {
    /// Date-only ISO8601 formatter for API dates
    static let apiDateOnly: ISO8601DateFormatter = {
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withFullDate, .withDashSeparatorInDate]
        return formatter
    }()
}

extension String {
    /// Converts API date string (YYYY-MM-DD) to Date
    func toDate() -> Date? {
        return ISO8601DateFormatter.apiDateOnly.date(from: self)
    }
}

extension Date {
    /// Converts Date to API date string (YYYY-MM-DD)
    func toAPIDateString() -> String {
        return ISO8601DateFormatter.apiDateOnly.string(from: self)
    }
    
    /// Converts Date to user-friendly display string
    func toDisplayString() -> String {
        return DateFormatter.displayDateFormatter.string(from: self)
    }
}
