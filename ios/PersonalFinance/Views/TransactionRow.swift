import SwiftUI

struct TransactionRow: View {
    let transaction: Transaction

    var body: some View {
        HStack {
            Circle()
                .fill(Color(hex: transaction.category?.color ?? "") ?? Color.gray.opacity(0.3))
                .frame(width: 10, height: 10)
            VStack(alignment: .leading, spacing: 4) {
                Text(transaction.category?.name ?? "Uncategorized")
                    .font(.headline)
                if let description = transaction.description, !description.isEmpty {
                    Text(description)
                        .font(.subheadline)
                        .foregroundStyle(.secondary)
                }
                Text(formatDate(transaction.date))
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
            Spacer()
            VStack(alignment: .trailing, spacing: 4) {
                Text(formatAmount(transaction.amount, currency: transaction.currency))
                    .font(.headline)
                Text(transaction.type.capitalized)
                    .font(.caption)
                    .foregroundStyle(.secondary)
            }
        }
        .padding(.vertical, 4)
    }

    private func formatAmount(_ amount: String, currency: String) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = currency
        formatter.minimumFractionDigits = 2
        formatter.maximumFractionDigits = 2
        let value = Double(amount) ?? 0
        return formatter.string(from: NSNumber(value: value)) ?? "\(amount) \(currency)"
    }

    private func formatDate(_ value: String) -> String {
        let iso = ISO8601DateFormatter()
        iso.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        let plain = ISO8601DateFormatter()
        if let date = iso.date(from: value) ?? plain.date(from: value) {
            let formatter = DateFormatter()
            formatter.dateStyle = .medium
            return formatter.string(from: date)
        }
        return value
    }
}
