import SwiftUI

struct NewBudgetView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let onSave: (Budget) -> Void

    @State private var amount = ""
    @State private var currency = ""
    @State private var period = "monthly"
    @State private var startDate = Date()
    @State private var endDate = Date()
    @State private var hasEndDate = false
    @State private var categories: [Category] = []
    @State private var selectedCategoryId: Int?
    @State private var selectedSubcategoryId: Int?
    @State private var errorMessage: String?
    @State private var isLoading = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Budget") {
                    TextField("Amount", text: $amount)
                        .keyboardType(.decimalPad)

                    if appState.currencies.isEmpty {
                        TextField("Currency", text: $currency)
                            .textInputAutocapitalization(.characters)
                    } else {
                        Picker("Currency", selection: $currency) {
                            ForEach(appState.currencies) { currencyInfo in
                                Text("\(currencyInfo.code) • \(currencyInfo.name)")
                                    .tag(currencyInfo.code)
                            }
                        }
                    }

                    Picker("Period", selection: $period) {
                        Text("Monthly").tag("monthly")
                        Text("Yearly").tag("yearly")
                    }

                    DatePicker("Start Date", selection: $startDate, displayedComponents: .date)

                    Toggle("End Date", isOn: $hasEndDate)
                    if hasEndDate {
                        DatePicker("", selection: $endDate, displayedComponents: .date)
                    }
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

                if let errorMessage {
                    Section {
                        Text(errorMessage)
                            .foregroundStyle(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("New Budget")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button("Save") {
                        Task { await saveBudget() }
                    }
                    .disabled(isLoading || amount.isEmpty || currency.isEmpty)
                }
            }
            .task {
                await loadCategories()
                await appState.fetchCurrencies()
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

    private func saveBudget() async {
        guard let amountValue = Double(amount) else {
            errorMessage = "Enter a valid amount."
            return
        }

        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let request = CreateBudgetRequest(
                categoryId: selectedCategoryId,
                subcategoryId: selectedSubcategoryId,
                amount: amountValue,
                currency: currency,
                period: period,
                startDate: startDate.toAPIDateString(),
                endDate: hasEndDate ? endDate.toAPIDateString() : nil
            )
            let budget = try await appState.createBudget(request)
            onSave(budget)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
