import SwiftUI

struct CategoriesView: View {
    @EnvironmentObject private var appState: AppState
    @State private var categories: [Category] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewCategory = false
    @State private var selectedCategoryForSub: Category?

    var body: some View {
        NavigationStack {
            List {
                ForEach(categories) { category in
                    Section {
                        if let subs = category.subcategories, !subs.isEmpty {
                            ForEach(subs) { sub in
                                Text(sub.name)
                                    .font(.subheadline)
                                    .foregroundStyle(.secondary)
                            }
                        } else {
                            Text("No subcategories")
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    } header: {
                        HStack {
                            Text(category.name)
                            Spacer()
                            Button {
                                selectedCategoryForSub = category
                            } label: {
                                Image(systemName: "plus")
                            }
                            .buttonStyle(.borderless)
                        }
                    }
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading categories...")
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
            .sheet(isPresented: $showingNewCategory) {
                NewCategoryView { newCategory in
                    categories.insert(newCategory, at: 0)
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
}
