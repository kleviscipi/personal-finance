import SwiftUI

struct TransactionsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var transactions: [Transaction] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewTransaction = false

    var body: some View {
        NavigationStack {
            List(transactions) { transaction in
                TransactionRow(transaction: transaction)
                    .cardStyle()
                    .listRowSeparator(.hidden)
                    .listRowBackground(Color.clear)
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading transactions...")
                }
            }
            .navigationTitle("Transactions")
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button {
                        Task { await loadTransactions() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        showingNewTransaction = true
                    } label: {
                        Image(systemName: "plus")
                    }
                }
            }
            .task {
                await loadTransactions()
            }
            .listStyle(.plain)
            .sheet(isPresented: $showingNewTransaction) {
                NewTransactionView { newTransaction in
                    transactions.insert(newTransaction, at: 0)
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

    private func loadTransactions() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            transactions = try await appState.fetchTransactions()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
