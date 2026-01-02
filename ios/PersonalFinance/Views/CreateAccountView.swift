import SwiftUI

struct CreateAccountView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    @State private var name = ""
    @State private var currency = "USD"
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Account Details") {
                    TextField("Account Name", text: $name)
                        .textInputAutocapitalization(.words)
                    
                    Picker("Base Currency", selection: $currency) {
                        ForEach(appState.availableCurrencies, id: \.code) { curr in
                            Text("\(curr.code) - \(curr.name)").tag(curr.code)
                        }
                    }
                }
                
                Section {
                    Text("Create a separate account for personal or family finances. Each account has its own transactions, budgets, and members.")
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                
                if let error = errorMessage {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }
            }
            .navigationTitle("Create Account")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Create") {
                        Task {
                            await createAccount()
                        }
                    }
                    .disabled(!isValid || isLoading)
                }
            }
        }
    }
    
    private var isValid: Bool {
        !name.isEmpty
    }
    
    private func createAccount() async {
        guard isValid else { return }
        
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        
        do {
            try await appState.createAccount(name: name, currency: currency)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
