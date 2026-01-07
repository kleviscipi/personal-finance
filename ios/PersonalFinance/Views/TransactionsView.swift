import SwiftUI

struct TransactionsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var transactions: [Transaction] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingNewTransaction = false
    @State private var transactionToDelete: Transaction?
    @State private var showingDeleteAlert = false

    var body: some View {
        NavigationStack {
            List(transactions) { transaction in
                transactionRow(for: transaction)
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading transactions...")
                } else if transactions.isEmpty {
                    ContentUnavailableView(
                        "No Transactions",
                        systemImage: "list.bullet",
                        description: Text("Add your first transaction to get started")
                    )
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
            .alert("Delete Transaction", isPresented: $showingDeleteAlert, presenting: transactionToDelete) { transaction in
                Button("Cancel", role: .cancel) {
                    transactionToDelete = nil
                }
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteTransaction(transaction)
                    }
                }
            } message: { transaction in
                Text("Are you sure you want to delete this transaction? This action cannot be undone.")
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

    @ViewBuilder
    private func transactionRow(for transaction: Transaction) -> some View {
        TransactionRow(transaction: transaction)
            .cardStyle()
            .listRowSeparator(.hidden)
            .listRowBackground(Color.clear)
            .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                Button(role: .destructive) {
                    transactionToDelete = transaction
                    showingDeleteAlert = true
                } label: {
                    Label("Delete", systemImage: "trash")
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
    
    private func deleteTransaction(_ transaction: Transaction) async {
        do {
            try await appState.deleteTransaction(transaction.id)
            transactions.removeAll { $0.id == transaction.id }
            transactionToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
