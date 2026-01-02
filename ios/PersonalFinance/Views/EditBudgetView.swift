import SwiftUI

struct EditBudgetView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    let budget: Budget
    let onSave: (Budget) -> Void
    
    @State private var amount: String
    @State private var currency: String
    @State private var period: String
    @State private var startDate: Date
    @State private var endDate: Date
    @State private var selectedCategoryId: Int?
    @State private var selectedSubcategoryId: Int?
    
    @State private var categories: [Category] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    let periods = ["monthly", "yearly", "custom"]
    
    init(budget: Budget, onSave: @escaping (Budget) -> Void) {
        self.budget = budget
        self.onSave = onSave
        
        _amount = State(initialValue: budget.amount.raw)
        _currency = State(initialValue: budget.currency)
        _period = State(initialValue: budget.period)
        _selectedCategoryId = State(initialValue: budget.category?.id)
        _selectedSubcategoryId = State(initialValue: budget.subcategory?.id)
        
        _startDate = State(initialValue: budget.startDate.toDate() ?? Date())
        _endDate = State(initialValue: budget.endDate?.toDate() ?? Date())
    }
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Budget Details") {
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
                    
                    Picker("Period", selection: $period) {
                        ForEach(periods, id: \.self) { p in
                            Text(p.capitalized).tag(p)
                        }
                    }
                }
                
                Section("Timeline") {
                    DatePicker("Start Date", selection: $startDate, displayedComponents: .date)
                    
                    if period == "custom" {
                        DatePicker("End Date", selection: $endDate, displayedComponents: .date)
                    }
                }
                
                Section("Category") {
                    Picker("Category", selection: $selectedCategoryId) {
                        Text("All Categories").tag(nil as Int?)
                        ForEach(expenseCategories) { category in
                            Text(category.name).tag(category.id as Int?)
                        }
                    }
                    
                    if let categoryId = selectedCategoryId,
                       let category = categories.first(where: { $0.id == categoryId }),
                       let subs = category.subcategories, !subs.isEmpty {
                        Picker("Subcategory", selection: $selectedSubcategoryId) {
                            Text("All Subcategories").tag(nil as Int?)
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
            .navigationTitle("Edit Budget")
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
    
    private var expenseCategories: [Category] {
        categories.filter { $0.type == "expense" }
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
            let request = UpdateBudgetRequest(
                categoryId: selectedCategoryId,
                subcategoryId: selectedSubcategoryId,
                amount: Double(amount) ?? 0,
                currency: currency,
                period: period,
                startDate: startDate.toAPIDateString(),
                endDate: period == "custom" ? endDate.toAPIDateString() : nil
            )
            
            let updated = try await appState.updateBudget(budget.id, request: request)
            onSave(updated)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
