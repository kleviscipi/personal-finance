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
    @State private var paymentMethod = ""
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

                    TextField("Currency", text: $currency)
                        .textInputAutocapitalization(.characters)

                    DatePicker("Date", selection: $date, displayedComponents: .date)
                }

                Section("Category") {
                    Picker("Category", selection: $selectedCategoryId) {
                        Text("None").tag(Int?.none)
                        ForEach(categories) { category in
                            Text(category.name).tag(Int?.some(category.id))
                        }
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
                    TextField("Payment Method", text: $paymentMethod)
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
                    Button("Save") {
                        Task { await saveTransaction() }
                    }
                    .disabled(isLoading || amount.isEmpty || currency.isEmpty)
                }
            }
            .task {
                await loadCategories()
                if currency.isEmpty {
                    currency = appState.activeAccount?.baseCurrency ?? "USD"
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
        guard let amountValue = Double(amount) else {
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
}
