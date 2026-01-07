import SwiftUI

struct AccountPickerView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        NavigationStack {
            List {
                ForEach(appState.accounts) { account in
                    Button {
                        appState.selectAccount(account)
                    } label: {
                        VStack(alignment: .leading, spacing: 4) {
                            Text(account.name)
                                .font(.headline)
                            Text(account.baseCurrency)
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                        }
                    }
                }
            }
            .navigationTitle("Select Account")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button("Sign Out") {
                        Task { await appState.logout() }
                    }
                }
            }
        }
    }
}
