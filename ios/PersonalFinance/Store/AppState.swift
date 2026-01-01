import Foundation

@MainActor
final class AppState: ObservableObject {
    @Published var token: String?
    @Published var user: User?
    @Published var accounts: [Account] = []
    @Published var activeAccount: Account?
    @Published var currencies: [CurrencyInfo] = []
    @Published var isLoading = false
    @Published var errorMessage: String?

    let client: APIClient

    private let tokenKey = "pf.api.token"

    init(client: APIClient = APIClient(baseURL: AppConfig.apiBaseURL)) {
        self.client = client
        self.token = UserDefaults.standard.string(forKey: tokenKey)
        self.client.token = token
    }

    func login(email: String, password: String) async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let payload = LoginRequest(email: email, password: password, deviceName: "ios")
            let response: APIResponse<AuthPayload> = try await client.request(
                "auth/login",
                method: "POST",
                body: payload
            )

            token = response.data.token
            client.token = response.data.token
            UserDefaults.standard.set(response.data.token, forKey: tokenKey)
            user = response.data.user
            await fetchMe()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func fetchMe() async {
        guard token != nil else { return }
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            let response: APIResponse<MePayload> = try await client.request("auth/me")
            user = response.data.user
            accounts = response.data.accounts

            if activeAccount == nil {
                activeAccount = accounts.first(where: { $0.isActive }) ?? accounts.first
            }

            await fetchCurrencies()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func logout() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }

        do {
            try await client.requestVoid("auth/logout", method: "POST")
        } catch {
            errorMessage = error.localizedDescription
        }

        token = nil
        user = nil
        accounts = []
        activeAccount = nil
        client.token = nil
        UserDefaults.standard.removeObject(forKey: tokenKey)
    }

    func selectAccount(_ account: Account) {
        activeAccount = account
    }

    func fetchTransactions() async throws -> [Transaction] {
        guard let accountId = activeAccount?.id else {
            return []
        }

        let response: APICollectionResponse<Transaction> = try await client.request(
            "transactions",
            accountId: accountId
        )

        return response.data
    }

    func fetchCurrencies() async {
        guard currencies.isEmpty else { return }

        do {
            let response: APICollectionResponse<CurrencyInfo> = try await client.request("meta/currencies")
            currencies = response.data
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    func fetchDashboard() async throws -> DashboardPayload {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<DashboardPayload> = try await client.request(
            "dashboard",
            accountId: accountId
        )

        return response.data
    }

    func fetchStatistics(start: String? = nil, end: String? = nil) async throws -> StatisticsPayload {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        var path = "statistics"
        if let start, let end {
            path += "?start=\(start)&end=\(end)"
        }

        let response: APIResponse<StatisticsPayload> = try await client.request(
            path,
            accountId: accountId
        )

        return response.data
    }

    func fetchCategories() async throws -> [Category] {
        guard let accountId = activeAccount?.id else {
            return []
        }

        let response: APICollectionResponse<Category> = try await client.request(
            "categories",
            accountId: accountId
        )

        return response.data
    }

    func fetchBudgets() async throws -> [Budget] {
        guard let accountId = activeAccount?.id else {
            return []
        }

        let response: APICollectionResponse<Budget> = try await client.request(
            "budgets",
            accountId: accountId
        )

        return response.data
    }

    func createTransaction(_ request: CreateTransactionRequest) async throws -> Transaction {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Transaction> = try await client.request(
            "transactions",
            method: "POST",
            body: request,
            accountId: accountId
        )

        return response.data
    }

    func createBudget(_ request: CreateBudgetRequest) async throws -> Budget {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Budget> = try await client.request(
            "budgets",
            method: "POST",
            body: request,
            accountId: accountId
        )

        return response.data
    }

    func createCategory(_ request: CreateCategoryRequest) async throws -> Category {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Category> = try await client.request(
            "categories",
            method: "POST",
            body: request,
            accountId: accountId
        )

        return response.data
    }

    func createSubcategory(categoryId: Int, request: CreateSubcategoryRequest) async throws -> Subcategory {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Subcategory> = try await client.request(
            "categories/\(categoryId)/subcategories",
            method: "POST",
            body: request,
            accountId: accountId
        )

        return response.data
    }
}

private struct LoginRequest: Encodable {
    let email: String
    let password: String
    let deviceName: String
}

struct CreateTransactionRequest: Encodable {
    let type: String
    let amount: Double
    let currency: String
    let date: String
    let categoryId: Int?
    let subcategoryId: Int?
    let description: String?
    let paymentMethod: String?
}

struct CreateBudgetRequest: Encodable {
    let categoryId: Int?
    let subcategoryId: Int?
    let amount: Double
    let currency: String
    let period: String
    let startDate: String
    let endDate: String?
}

struct CreateCategoryRequest: Encodable {
    let name: String
    let type: String
    let icon: String?
    let color: String?
    let order: Int?
}

struct CreateSubcategoryRequest: Encodable {
    let name: String
    let order: Int?
}
