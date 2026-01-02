import SwiftUI

struct EditSavingsGoalView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    let goal: SavingsGoal
    let onSave: (SavingsGoal) -> Void
    
    @State private var name: String
    @State private var targetAmount: String
    @State private var initialAmount: String
    @State private var currency: String
    @State private var trackingMode: String
    @State private var startDate: Date
    @State private var targetDate: Date
    @State private var selectedCategoryId: Int?
    @State private var selectedSubcategoryId: Int?
    
    @State private var categories: [Category] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    let trackingModes = [
        ("manual", "Manual only"),
        ("net_savings", "Net savings"),
        ("category", "Category income"),
        ("subcategory", "Subcategory income")
    ]
    
    init(goal: SavingsGoal, onSave: @escaping (SavingsGoal) -> Void) {
        self.goal = goal
        self.onSave = onSave
        
        _name = State(initialValue: goal.name)
        _targetAmount = State(initialValue: goal.targetAmount.raw)
        _initialAmount = State(initialValue: goal.initialAmount?.raw ?? "")
        _currency = State(initialValue: goal.currency)
        _trackingMode = State(initialValue: goal.trackingMode)
        
        _startDate = State(initialValue: goal.startDate.toDate() ?? Date())
        _targetDate = State(initialValue: goal.targetDate.toDate() ?? Date())
    }
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Goal Details") {
                    TextField("Name", text: $name)
                    
                    HStack {
                        Text("Target Amount")
                        TextField("0.00", text: $targetAmount)
                            .keyboardType(.decimalPad)
                            .multilineTextAlignment(.trailing)
                    }
                    
                    HStack {
                        Text("Initial Amount")
                        TextField("0.00", text: $initialAmount)
                            .keyboardType(.decimalPad)
                            .multilineTextAlignment(.trailing)
                    }
                    
                    Picker("Currency", selection: $currency) {
                        ForEach(appState.availableCurrencies, id: \.code) { curr in
                            Text("\(curr.code) (\(curr.symbol))").tag(curr.code)
                        }
                    }
                }
                
                Section("Timeline") {
                    DatePicker("Start Date", selection: $startDate, displayedComponents: .date)
                    DatePicker("Target Date", selection: $targetDate, displayedComponents: .date)
                }
                
                Section("Tracking") {
                    Picker("Tracking Mode", selection: $trackingMode) {
                        ForEach(trackingModes, id: \.0) { mode in
                            Text(mode.1).tag(mode.0)
                        }
                    }
                    
                    if trackingMode == "category" || trackingMode == "subcategory" {
                        Picker("Category", selection: $selectedCategoryId) {
                            Text("Select Category").tag(nil as Int?)
                            ForEach(incomeCategories) { category in
                                Text(category.name).tag(category.id as Int?)
                            }
                        }
                        
                        if trackingMode == "subcategory", let categoryId = selectedCategoryId {
                            if let category = categories.first(where: { $0.id == categoryId }),
                               let subs = category.subcategories, !subs.isEmpty {
                                Picker("Subcategory", selection: $selectedSubcategoryId) {
                                    Text("Select Subcategory").tag(nil as Int?)
                                    ForEach(subs) { sub in
                                        Text(sub.name).tag(sub.id as Int?)
                                    }
                                }
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
            .navigationTitle("Edit Savings Goal")
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
    
    private var incomeCategories: [Category] {
        categories.filter { $0.type == "income" }
    }
    
    private var isValid: Bool {
        !name.isEmpty && 
        !targetAmount.isEmpty && 
        Double(targetAmount) != nil &&
        Double(targetAmount) ?? 0 > 0
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
            let request = UpdateSavingsGoalRequest(
                name: name,
                targetAmount: Double(targetAmount) ?? 0,
                initialAmount: initialAmount.isEmpty ? nil : Double(initialAmount),
                currency: currency,
                trackingMode: trackingMode,
                startDate: startDate.toAPIDateString(),
                targetDate: targetDate.toAPIDateString(),
                categoryId: trackingMode == "category" || trackingMode == "subcategory" ? selectedCategoryId : nil,
                subcategoryId: trackingMode == "subcategory" ? selectedSubcategoryId : nil
            )
            
            let updatedGoal = try await appState.updateSavingsGoal(goal.id, request: request)
            onSave(updatedGoal)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
