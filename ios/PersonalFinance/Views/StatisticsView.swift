import SwiftUI

struct StatisticsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var payload: StatisticsPayload?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            List {
                if let summaries = payload?.analytics.monthlySummary {
                    Section("Monthly Summary") {
                        ForEach(summaries) { summary in
                            VStack(alignment: .leading, spacing: 4) {
                                Text(summary.month)
                                    .font(.subheadline.bold())
                                Text("Income: \(summary.income.raw)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Text("Expenses: \(summary.expenses.raw)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                                Text("Net: \(summary.net.raw)")
                                    .font(.caption)
                                    .foregroundStyle(.secondary)
                            }
                            .cardStyle()
                            .listRowSeparator(.hidden)
                            .listRowBackground(Color.clear)
                        }
                    }
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading statistics...")
                }
            }
            .navigationTitle("Statistics")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Button {
                        Task { await loadStatistics() }
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
            }
            .task {
                await loadStatistics()
            }
            .listStyle(.plain)
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

    private func loadStatistics() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            payload = try await appState.fetchStatistics()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
