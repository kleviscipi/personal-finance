import SwiftUI

struct CategoriesView: View {
    @EnvironmentObject private var appState: AppState
    @State private var categories: [Category] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewCategory = false
    @State private var selectedCategoryForSub: Category?
    @State private var selectedCategory: Category?
    @State private var categoryToDelete: Category?
    @State private var showingDeleteCategoryAlert = false
    @State private var selectedSubcategory: (Category, Subcategory)?
    @State private var subcategoryToDelete: (Category, Subcategory)?
    @State private var showingDeleteSubcategoryAlert = false

    var body: some View {
        NavigationStack {
            List {
                ForEach(categories) { category in
                    VStack(alignment: .leading, spacing: 8) {
                        HStack {
                            Circle()
                                .fill(Color(hex: category.color ?? "") ?? Color.gray.opacity(0.3))
                                .frame(width: 10, height: 10)
                            Text(category.name)
                                .font(.headline)
                            Spacer()
                            Button {
                                selectedCategoryForSub = category
                            } label: {
                                Image(systemName: "plus")
                            }
                            .buttonStyle(.borderless)
                        }
                        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                            if !category.isSystem {
                                Button(role: .destructive) {
                                    categoryToDelete = category
                                    showingDeleteCategoryAlert = true
                                } label: {
                                    Label("Delete", systemImage: "trash")
                                }
                            }
                            
                            Button {
                                selectedCategory = category
                            } label: {
                                Label("Edit", systemImage: "pencil")
                            }
                            .tint(.blue)
                        }

                        if let subs = category.subcategories, !subs.isEmpty {
                            ForEach(subs) { sub in
                                HStack {
                                    Text(sub.name)
                                        .font(.subheadline)
                                        .foregroundStyle(.secondary)
                                    Spacer()
                                }
                                .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                                    Button(role: .destructive) {
                                        subcategoryToDelete = (category, sub)
                                        showingDeleteSubcategoryAlert = true
                                    } label: {
                                        Label("Delete", systemImage: "trash")
                                    }
                                    
                                    Button {
                                        selectedSubcategory = (category, sub)
                                    } label: {
                                        Label("Edit", systemImage: "pencil")
                                    }
                                    .tint(.blue)
                                }
                            }
                        } else {
                            Text("No subcategories")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    }
                    .cardStyle()
                    .listRowSeparator(.hidden)
                    .listRowBackground(Color.clear)
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading categories...")
                } else if categories.isEmpty {
                    ContentUnavailableView(
                        "No Categories",
                        systemImage: "folder",
                        description: Text("Create a category to organize your transactions")
                    )
                }
            }
            .navigationTitle("Categories")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button {
                        Task { await loadCategories() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        showingNewCategory = true
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .task {
                await loadCategories()
            }
            .listStyle(.plain)
            .sheet(isPresented: $showingNewCategory) {
                NewCategoryView { newCategory in
                    categories.insert(newCategory, at: 0)
                }
            }
            .sheet(item: $selectedCategory) { category in
                EditCategoryView(category: category) { updated in
                    if let index = categories.firstIndex(where: { $0.id == category.id }) {
                        categories[index] = updated
                    }
                }
            }
            .sheet(item: $selectedCategoryForSub) { category in
                NewSubcategoryView(category: category) { subcategory in
                    if let index = categories.firstIndex(where: { $0.id == category.id }) {
                        var updated = categories[index]
                        var subs = updated.subcategories ?? []
                        subs.append(subcategory)
                        updated = Category(
                            id: updated.id,
                            accountId: updated.accountId,
                            name: updated.name,
                            icon: updated.icon,
                            color: updated.color,
                            type: updated.type,
                            isSystem: updated.isSystem,
                            order: updated.order,
                            subcategories: subs
                        )
                        categories[index] = updated
                    }
                }
            }
            .sheet(item: Binding(
                get: { selectedSubcategory.map { SubcategoryWrapper(category: $0.0, subcategory: $0.1) } },
                set: { selectedSubcategory = $0.map { ($0.category, $0.subcategory) } }
            )) { wrapper in
                EditSubcategoryView(category: wrapper.category, subcategory: wrapper.subcategory) { updated in
                    if let catIndex = categories.firstIndex(where: { $0.id == wrapper.category.id }) {
                        var cat = categories[catIndex]
                        if var subs = cat.subcategories,
                           let subIndex = subs.firstIndex(where: { $0.id == wrapper.subcategory.id }) {
                            subs[subIndex] = updated
                            cat = Category(
                                id: cat.id,
                                accountId: cat.accountId,
                                name: cat.name,
                                icon: cat.icon,
                                color: cat.color,
                                type: cat.type,
                                isSystem: cat.isSystem,
                                order: cat.order,
                                subcategories: subs
                            )
                            categories[catIndex] = cat
                        }
                    }
                }
            }
            .alert("Delete Category", isPresented: $showingDeleteCategoryAlert, presenting: categoryToDelete) { category in
                Button("Cancel", role: .cancel) {
                    categoryToDelete = nil
                }
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteCategory(category)
                    }
                }
            } message: { category in
                Text("Are you sure you want to delete '\(category.name)'? This will also delete all subcategories.")
            }
            .alert("Delete Subcategory", isPresented: $showingDeleteSubcategoryAlert, presenting: subcategoryToDelete) { pair in
                Button("Cancel", role: .cancel) {
                    subcategoryToDelete = nil
                }
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteSubcategory(pair.0, pair.1)
                    }
                }
            } message: { pair in
                Text("Are you sure you want to delete '\(pair.1.name)'?")
            }
            .alert("Error", isPresented: Binding(
                get: { errorMessage != nil },
                set: { if !$0 { errorMessage = nil } }
            )) {
                Button("OK", role: .cancel) {}
            } message: {
                Text(errorMessage ?? "")
            }
        }
    }

    private func loadCategories() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            categories = try await appState.fetchCategories()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func deleteCategory(_ category: Category) async {
        do {
            try await appState.deleteCategory(category.id)
            categories.removeAll { $0.id == category.id }
            categoryToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func deleteSubcategory(_ category: Category, _ subcategory: Subcategory) async {
        do {
            try await appState.deleteSubcategory(categoryId: category.id, subcategoryId: subcategory.id)
            if let index = categories.firstIndex(where: { $0.id == category.id }) {
                var updated = categories[index]
                var subs = updated.subcategories ?? []
                subs.removeAll { $0.id == subcategory.id }
                updated = Category(
                    id: updated.id,
                    accountId: updated.accountId,
                    name: updated.name,
                    icon: updated.icon,
                    color: updated.color,
                    type: updated.type,
                    isSystem: updated.isSystem,
                    order: updated.order,
                    subcategories: subs
                )
                categories[index] = updated
            }
            subcategoryToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private struct SubcategoryWrapper: Identifiable {
    let id = UUID()
    let category: Category
    let subcategory: Subcategory
}
