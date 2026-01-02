import SwiftUI

struct EditCategoryView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    let category: Category
    let onSave: (Category) -> Void
    
    @State private var name: String
    @State private var type: String
    @State private var icon: String
    @State private var color: String
    
    @State private var isLoading = false
    @State private var errorMessage: String?
    
    let categoryTypes = ["expense", "income"]
    let availableIcons = ["cart", "house", "car", "fork.knife", "heart", "gift", "briefcase", "creditcard"]
    let availableColors = ["#EF4444", "#F59E0B", "#10B981", "#3B82F6", "#8B5CF6", "#EC4899", "#6B7280"]
    
    init(category: Category, onSave: @escaping (Category) -> Void) {
        self.category = category
        self.onSave = onSave
        
        _name = State(initialValue: category.name)
        _type = State(initialValue: category.type)
        _icon = State(initialValue: category.icon ?? "cart")
        _color = State(initialValue: category.color ?? "#3B82F6")
    }
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Category Details") {
                    TextField("Name", text: $name)
                    
                    Picker("Type", selection: $type) {
                        ForEach(categoryTypes, id: \.self) { t in
                            Text(t.capitalized).tag(t)
                        }
                    }
                }
                
                Section("Icon") {
                    ScrollView(.horizontal, showsIndicators: false) {
                        HStack(spacing: 16) {
                            ForEach(availableIcons, id: \.self) { iconName in
                                Circle()
                                    .fill(icon == iconName ? Color.blue : Color.gray.opacity(0.2))
                                    .frame(width: 50, height: 50)
                                    .overlay {
                                        Image(systemName: iconName)
                                            .foregroundColor(icon == iconName ? .white : .gray)
                                    }
                                    .onTapGesture {
                                        icon = iconName
                                    }
                            }
                        }
                        .padding(.vertical, 8)
                    }
                }
                
                Section("Color") {
                    ScrollView(.horizontal, showsIndicators: false) {
                        HStack(spacing: 16) {
                            ForEach(availableColors, id: \.self) { hex in
                                Circle()
                                    .fill(Color(hex: hex) ?? .gray)
                                    .frame(width: 40, height: 40)
                                    .overlay {
                                        if color == hex {
                                            Image(systemName: "checkmark")
                                                .foregroundColor(.white)
                                                .fontWeight(.bold)
                                        }
                                    }
                                    .onTapGesture {
                                        color = hex
                                    }
                            }
                        }
                        .padding(.vertical, 8)
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
            .navigationTitle("Edit Category")
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
            let request = UpdateCategoryRequest(
                name: name,
                type: type,
                icon: icon,
                color: color,
                order: category.order
            )
            
            let updated = try await appState.updateCategory(category.id, request: request)
            onSave(updated)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
