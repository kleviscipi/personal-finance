import SwiftUI

struct NewCategoryView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let onSave: (Category) -> Void

    @State private var name = ""
    @State private var type = "expense"
    @State private var icon = ""
    @State private var color = ""
    @State private var errorMessage: String?
    @State private var isLoading = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Category") {
                    TextField("Name", text: $name)

                    Picker("Type", selection: $type) {
                        Text("Expense").tag("expense")
                        Text("Income").tag("income")
                    }

                    TextField("Icon", text: $icon)
                    TextField("Color (#RRGGBB)", text: $color)
                        .textInputAutocapitalization(.characters)
                }

                if let errorMessage {
                    Section {
                        Text(errorMessage)
                            .foregroundStyle(.red)
                            .font(.footnote)
                    }
                }
            }
            .navigationTitle("New Category")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button("Save") { Task { await saveCategory() } }
                        .disabled(isLoading || name.isEmpty)
                }
            }
        }
    }

    private func saveCategory() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let request = CreateCategoryRequest(
                name: name,
                type: type,
                icon: icon.isEmpty ? nil : icon,
                color: color.isEmpty ? nil : color,
                order: nil
            )
            let category = try await appState.createCategory(request)
            onSave(category)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
