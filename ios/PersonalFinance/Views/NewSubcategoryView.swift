import SwiftUI

struct NewSubcategoryView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let category: Category
    let onSave: (Subcategory) -> Void

    @State private var name = ""
    @State private var errorMessage: String?
    @State private var isLoading = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Subcategory") {
                    TextField("Name", text: $name)
                }

                if let errorMessage {
                    Section {
                        Text(errorMessage)
                            .foregroundStyle(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("New Subcategory")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button("Save") { Task { await saveSubcategory() } }
                        .disabled(isLoading || name.isEmpty)
                }
            }
        }
    }

    private func saveSubcategory() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let request = CreateSubcategoryRequest(name: name, order: nil)
            let subcategory = try await appState.createSubcategory(categoryId: category.id, request: request)
            onSave(subcategory)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
