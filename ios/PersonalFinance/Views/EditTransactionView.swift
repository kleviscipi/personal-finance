import SwiftUI

struct EditTransactionView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    let transaction: Transaction
    let onSave: (Transaction) -> Void
    
    @State private var type: String
    @State private var amount: String
    @State private var currency: String
    @State private var date: Date
    @State private var description: String
    @State private var paymentMethod: String
    @State private var selectedCategoryId: Int?
    @State private var selectedSubcategoryId: Int?
    
    @State private var categories: [Category] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    let transactionTypes = ["expense", "income", "transfer"]
    let paymentMethods = ["cash", "card", "bank_transfer", "other"]
    
    init(transaction: Transaction, onSave: @escaping (Transaction) -> Void) {
        self.transaction = transaction
        self.onSave = onSave
        
        _type = State(initialValue: transaction.type)
        _amount = State(initialValue: transaction.amount.raw)
        _currency = State(initialValue: transaction.currency)
        _description = State(initialValue: transaction.description ?? "")
        _paymentMethod = State(initialValue: transaction.paymentMethod ?? "cash")
        _selectedCategoryId = State(initialValue: transaction.category?.id)
        _selectedSubcategoryId = State(initialValue: transaction.subcategory?.id)
        
        let formatter = ISO8601DateFormatter()
        formatter.formatOptions = [.withFullDate, .withDashSeparatorInDate]
        _date = State(initialValue: formatter.date(from: transaction.date) ?? Date())
    }
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Transaction Details") {
                    Picker("Type", selection: $type) {
                        ForEach(transactionTypes, id: \.self) { type in
                            Text(type.capitalized).tag(type)
                        }
                    }
                    
                    HStack {
                        Text("Amount")
                        TextField("0.00", text: $amount)
                            .keyboardType(.decimalPad)
                            .multilineTextAlignment(.trailing)
                    }
                    
                    Picker("Currency", selection: $currency) {
                        ForEach(appState.availableCurrencies, id: \.code) { curr in
                            Text("\(curr.code) (\(curr.symbol))").tag(curr.code)
                        }
                    }
                    
                    DatePicker("Date", selection: $date, displayedComponents: .date)
                    
                    TextField("Description (optional)", text: $description)
                    
                    Picker("Payment Method", selection: $paymentMethod) {
                        ForEach(paymentMethods, id: \.self) { method in
                            Text(method.replacingOccurrences(of: "_", with: " ").capitalized).tag(method)
                        }
                    }
                }
                
                Section("Category") {
                    Picker("Category", selection: $selectedCategoryId) {
                        Text("None").tag(nil as Int?)
                        ForEach(filteredCategories) { category in
                            Text(category.name).tag(category.id as Int?)
                        }
                    }
                    
                    if let categoryId = selectedCategoryId,
                       let category = categories.first(where: { $0.id == categoryId }),
                       let subs = category.subcategories, !subs.isEmpty {
                        Picker("Subcategory", selection: $selectedSubcategoryId) {
                            Text("None").tag(nil as Int?)
                            ForEach(subs) { sub in
                                Text(sub.name).tag(sub.id as Int?)
                            }
                        }
                    }
                }
                
                if let error = errorMessage {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }
            }
            .navigationTitle("Edit Transaction")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            await save()
                        }
                    }
                    .disabled(!isValid || isLoading)
                }
            }
            .task {
                await loadCategories()
            }
        }
    }
    
    private var filteredCategories: [Category] {
        categories.filter { $0.type == type }
    }
    
    private var isValid: Bool {
        !amount.isEmpty && Double(amount) != nil && Double(amount) ?? 0 > 0
    }
    
    private func loadCategories() async {
        do {
            categories = try await appState.fetchCategories()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func save() async {
        guard isValid else { return }
        
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        
        do {
            let formatter = ISO8601DateFormatter()
            formatter.formatOptions = [.withFullDate, .withDashSeparatorInDate]
            
            let request = UpdateTransactionRequest(
                type: type,
                amount: Double(amount) ?? 0,
                currency: currency,
                date: formatter.string(from: date),
                categoryId: selectedCategoryId,
                subcategoryId: selectedSubcategoryId,
                description: description.isEmpty ? nil : description,
                paymentMethod: paymentMethod
            )
            
            let updated = try await appState.updateTransaction(transaction.id, request: request)
            onSave(updated)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
