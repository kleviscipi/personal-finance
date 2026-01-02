import SwiftUI

struct EditSubcategoryView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    let category: Category
    let subcategory: Subcategory
    let onSave: (Subcategory) -> Void
    
    @State private var name: String
    
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    init(category: Category, subcategory: Subcategory, onSave: @escaping (Subcategory) -> Void) {
        self.category = category
        self.subcategory = subcategory
        self.onSave = onSave
        
        _name = State(initialValue: subcategory.name)
    }
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Subcategory Details") {
                    TextField("Name", text: $name)
                }
                
                Section {
                    Text("Parent Category")
                        .foregroundStyle(.secondary)
                    Text(category.name)
                        .font(.headline)
                }
                
                if let error = errorMessage {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }
            }
            .navigationTitle("Edit Subcategory")
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
        }
    }
    
    private var isValid: Bool {
        !name.isEmpty
    }
    
    private func save() async {
        guard isValid else { return }
        
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        
        do {
            let request = UpdateSubcategoryRequest(
                name: name,
                order: subcategory.order
            )
            
            let updated = try await appState.updateSubcategory(
                categoryId: category.id,
                subcategoryId: subcategory.id,
                request: request
            )
            onSave(updated)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
