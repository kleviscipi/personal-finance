import SwiftUI

struct MainTabView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        TabView {
            DashboardView()
                .tabItem {
                    Label("Dashboard", systemImage: "house")
                }

            TransactionsView()
                .tabItem {
                    Label("Transactions", systemImage: "list.bullet")
                }

            BudgetsView()
                .tabItem {
                    Label("Budgets", systemImage: "chart.pie")
                }
            
            CategoriesView()
                .tabItem {
                    Label("Categories", systemImage: "folder")
                }

            StatisticsView()
                .tabItem {
                    Label("Stats", systemImage: "chart.bar")
                }

            SettingsView()
                .tabItem {
                    Label("Settings", systemImage: "gearshape")
                }
        }
    }
}

struct SettingsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var showingAccountPicker = false

    var body: some View {
        NavigationStack {
            Form {
                if let user = appState.user {
                    Section("Account") {
                        HStack {
                            Text("Name")
                            Spacer()
                            Text(user.name)
                                .foregroundStyle(.secondary)
                        }
                        HStack {
                            Text("Email")
                            Spacer()
                            Text(user.email)
                                .foregroundStyle(.secondary)
                        }
                        
                    }
                }

                if let account = appState.activeAccount {
                    Section("Active Account") {
                        HStack {
                            Text("Name")
                            Spacer()
                            Text(account.name)
                                .foregroundStyle(.secondary)
                        }
                        HStack {
                            Text("Currency")
                            Spacer()
                            Text(account.baseCurrency)
                                .foregroundStyle(.secondary)
                        }
                        
                        Button {
                            showingAccountPicker = true
                        } label: {
                            HStack {
                                Text("Switch Account")
                                Spacer()
                                Image(systemName: "chevron.right")
                                    .foregroundStyle(.secondary)
                                    .font(.caption)
                            }
                        }
                        
                    }
                }

                Section {
                    Button("Sign Out", role: .destructive) {
                        Task { await appState.logout() }
                    }
                }
            }
            .navigationTitle("Settings")
            .sheet(isPresented: $showingAccountPicker) {
                NavigationStack {
                    List(appState.accounts) { account in
                        Button {
                            appState.selectAccount(account)
                            showingAccountPicker = false
                        } label: {
                            HStack {
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(account.name)
                                        .font(.headline)
                                    Text(account.baseCurrency)
                                        .font(.subheadline)
                                        .foregroundStyle(.secondary)
                                }
                                Spacer()
                                if account.id == appState.activeAccount?.id {
                                    Image(systemName: "checkmark")
                                        .foregroundColor(.blue)
                                }
                            }
                        }
                    }
                    .navigationTitle("Switch Account")
                    .navigationBarTitleDisplayMode(.inline)
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) {
                            Button("Cancel") {
                                showingAccountPicker = false
                            }
                        }
                    }
                }
            }
        }
    }
}
