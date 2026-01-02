import Foundation

struct ValidationUtils {
    /// Validates email address format
    /// - Parameter email: Email string to validate
    /// - Returns: True if email format is valid
    static func isValidEmail(_ email: String) -> Bool {
        let emailRegex = "^[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,64}$"
        let emailPredicate = NSPredicate(format: "SELF MATCHES %@", emailRegex)
        return emailPredicate.evaluate(with: email)
    }
    
    /// Validates password strength
    /// - Parameter password: Password string to validate
    /// - Returns: True if password meets minimum requirements
    static func isValidPassword(_ password: String) -> Bool {
        return password.count >= 8
    }
}
