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

    var body: some View {
        NavigationStack {
            Form {
                if let user = appState.user {
                    Section("Account") {
                        Text(user.name)
                        Text(user.email)
                            .foregroundStyle(.secondary)
                    }
                }

                if let account = appState.activeAccount {
                    Section("Active Account") {
                        Text(account.name)
                        Text(account.baseCurrency)
                            .foregroundStyle(.secondary)
                    }
                }

                Section {
                    Button("Sign Out", role: .destructive) {
                        Task { await appState.logout() }
                    }
                }
            }
            .navigationTitle("Settings")
        }
    }
}
