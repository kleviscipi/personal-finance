import SwiftUI

struct NewTransactionView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let onSave: (Transaction) -> Void

    @State private var type = "expense"
    @State private var amount = ""
    @State private var currency = ""
    @State private var date = Date()
    @State private var description = ""
    @State private var paymentMethod = "cash"
    @State private var categories: [Category] = []
    @State private var selectedCategoryId: Int?
    @State private var selectedSubcategoryId: Int?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            Form {
                Section("Details") {
                    Picker("Type", selection: $type) {
                        Text("Expense").tag("expense")
                        Text("Income").tag("income")
                        Text("Transfer").tag("transfer")
                    }

                    TextField("Amount", text: $amount)
                        .keyboardType(.decimalPad)
                        .onChange(of: amount) { newValue in
                            let normalized = normalizeAmountInput(newValue)
                            if normalized != newValue {
                                amount = normalized
                            }
                        }

                    Picker("Currency", selection: $currency) {
                        ForEach(appState.availableCurrencies) { currencyInfo in
                            Text("\(currencyInfo.code) • \(currencyInfo.name)")
                                .tag(currencyInfo.code)
                        }
                    }

                    DatePicker("Date", selection: $date, displayedComponents: .date)
                }

                Section("Category") {
                    Picker("Category", selection: $selectedCategoryId) {
                        Text("None").tag(Int?.none)
                        ForEach(categories) { category in
                            Text(category.name).tag(Int?.some(category.id))
                        }
                    }
                    .onChange(of: selectedCategoryId) { _ in
                        selectedSubcategoryId = nil
                    }

                    if let category = selectedCategory,
                       let subs = category.subcategories, !subs.isEmpty {
                        Picker("Subcategory", selection: $selectedSubcategoryId) {
                            Text("None").tag(Int?.none)
                            ForEach(subs) { sub in
                                Text(sub.name).tag(Int?.some(sub.id))
                            }
                        }
                    }
                }

                Section("Notes") {
                    TextField("Description", text: $description)
                    Picker("Payment Method", selection: $paymentMethod) {
                        ForEach(paymentOptions) { option in
                            Text(option.label).tag(option.value)
                        }
                    }
                }

                if let errorMessage {
                    Section {
                        Text(errorMessage)
                            .foregroundStyle(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("New Transaction")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await saveTransaction() }
                    } label: {
                        if isLoading {
                            ProgressView()
                        } else {
                            Text("Save")
                        }
                    }
                    .disabled(isLoading || amount.isEmpty || currency.isEmpty)
                }
            }
            .task {
                await loadCategories()
                await appState.fetchCurrencies()
                if currency.isEmpty {
                    currency = appState.activeAccount?.baseCurrency ?? appState.availableCurrencies.first?.code ?? "USD"
                }
            }
            .overlay {
                if isLoading {
                    ZStack {
                        Color.black.opacity(0.2).ignoresSafeArea()
                        ProgressView("Saving...")
                            .padding(16)
                            .background(Color(.systemBackground))
                            .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                    }
                }
            }
        }
    }

    private var selectedCategory: Category? {
        guard let selectedCategoryId else { return nil }
        return categories.first { $0.id == selectedCategoryId }
    }

    private func loadCategories() async {
        do {
            categories = try await appState.fetchCategories()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func saveTransaction() async {
        guard let amountValue = parseAmount(amount) else {
            errorMessage = "Enter a valid amount."
            return
        }

        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let request = CreateTransactionRequest(
                type: type,
                amount: amountValue,
                currency: currency,
                date: date.toAPIDateString(),
                categoryId: selectedCategoryId,
                subcategoryId: selectedSubcategoryId,
                description: description.isEmpty ? nil : description,
                paymentMethod: paymentMethod.isEmpty ? nil : paymentMethod
            )
            let transaction = try await appState.createTransaction(request)
            onSave(transaction)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func normalizeAmountInput(_ value: String) -> String {
        let replaced = value.replacingOccurrences(of: ",", with: ".")
        let filtered = replaced.filter { $0.isNumber || $0 == "." }
        var result = ""
        var dotSeen = false
        for char in filtered {
            if char == "." {
                if dotSeen { continue }
                dotSeen = true
            }
            result.append(char)
        }
        return result
    }

    private func parseAmount(_ value: String) -> Double? {
        let normalized = normalizeAmountInput(value)
        if let direct = Double(normalized) {
            return direct
        }

        let formatter = NumberFormatter()
        formatter.locale = Locale.current
        formatter.numberStyle = .decimal
        return formatter.number(from: value)?.doubleValue
    }

    private let paymentOptions: [PaymentOption] = [
        PaymentOption(value: "cash", label: "Cash"),
        PaymentOption(value: "card", label: "Card"),
        PaymentOption(value: "bank_transfer", label: "Bank transfer"),
        PaymentOption(value: "mobile_wallet", label: "Mobile wallet"),
        PaymentOption(value: "opening_balance", label: "Opening balance"),
        PaymentOption(value: "other", label: "Other"),
    ]
}

private struct PaymentOption: Identifiable {
    let value: String
    let label: String

    var id: String { value }
}
