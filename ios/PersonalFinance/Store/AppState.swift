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
    private let defaultCurrencies = [
        CurrencyInfo(name: "US Dollar", symbol: "$", code: "USD"),
        CurrencyInfo(name: "Euro", symbol: "EUR", code: "EUR"),
        CurrencyInfo(name: "Albanian Lek", symbol: "L", code: "ALL"),
    ]

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
    
    func register(name: String, email: String, password: String) async throws {
        let request = RegisterRequest(name: name, email: email, password: password, passwordConfirmation: password, deviceName: "ios")
        let response: APIResponse<AuthPayload> = try await client.request(
            "auth/register",
            method: "POST",
            body: request
        )

        token = response.data.token
        client.token = response.data.token
        UserDefaults.standard.set(response.data.token, forKey: tokenKey)
        user = response.data.user
        await fetchMe()
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

    var availableCurrencies: [CurrencyInfo] {
        currencies.isEmpty ? defaultCurrencies : currencies
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
    
    func fetchSavingsGoals() async throws -> [SavingsGoal] {
        guard let accountId = activeAccount?.id else {
            return []
        }

        let response: APICollectionResponse<SavingsGoal> = try await client.request(
            "savings-goals",
            accountId: accountId
        )

        return response.data
    }
    
    func createSavingsGoal(_ request: CreateSavingsGoalRequest) async throws -> SavingsGoal {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<SavingsGoal> = try await client.request(
            "savings-goals",
            method: "POST",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func updateSavingsGoal(_ id: Int, request: UpdateSavingsGoalRequest) async throws -> SavingsGoal {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<SavingsGoal> = try await client.request(
            "savings-goals/\(id)",
            method: "PATCH",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func deleteSavingsGoal(_ id: Int) async throws {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        try await client.requestVoid(
            "savings-goals/\(id)",
            method: "DELETE",
            accountId: accountId
        )
    }
    
    func updateTransaction(_ id: Int, request: UpdateTransactionRequest) async throws -> Transaction {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Transaction> = try await client.request(
            "transactions/\(id)",
            method: "PATCH",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func deleteTransaction(_ id: Int) async throws {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        try await client.requestVoid(
            "transactions/\(id)",
            method: "DELETE",
            accountId: accountId
        )
    }
    
    func updateBudget(_ id: Int, request: UpdateBudgetRequest) async throws -> Budget {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Budget> = try await client.request(
            "budgets/\(id)",
            method: "PATCH",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func deleteBudget(_ id: Int) async throws {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        try await client.requestVoid(
            "budgets/\(id)",
            method: "DELETE",
            accountId: accountId
        )
    }
    
    func updateCategory(_ id: Int, request: UpdateCategoryRequest) async throws -> Category {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Category> = try await client.request(
            "categories/\(id)",
            method: "PATCH",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func deleteCategory(_ id: Int) async throws {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        try await client.requestVoid(
            "categories/\(id)",
            method: "DELETE",
            accountId: accountId
        )
    }
    
    func updateSubcategory(categoryId: Int, subcategoryId: Int, request: UpdateSubcategoryRequest) async throws -> Subcategory {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        let response: APIResponse<Subcategory> = try await client.request(
            "categories/\(categoryId)/subcategories/\(subcategoryId)",
            method: "PATCH",
            body: request,
            accountId: accountId
        )

        return response.data
    }
    
    func deleteSubcategory(categoryId: Int, subcategoryId: Int) async throws {
        guard let accountId = activeAccount?.id else {
            throw APIError.server("No active account.")
        }

        try await client.requestVoid(
            "categories/\(categoryId)/subcategories/\(subcategoryId)",
            method: "DELETE",
            accountId: accountId
        )
    }
    
    func updateProfile(name: String, email: String) async throws {
        let request = UpdateProfileRequest(name: name, email: email)
        let response: APIResponse<User> = try await client.request(
            "profile",
            method: "PATCH",
            body: request
        )
        user = response.data
    }
    
    func updatePassword(currentPassword: String, newPassword: String) async throws {
        let request = UpdatePasswordRequest(
            currentPassword: currentPassword,
            password: newPassword,
            passwordConfirmation: newPassword
        )
        try await client.requestVoid(
            "profile/password",
            method: "PATCH",
            body: request
        )
    }
    
    func deleteAccount() async throws {
        try await client.requestVoid(
            "profile",
            method: "DELETE"
        )
        
        // Clear app state after deletion
        token = nil
        user = nil
        accounts = []
        activeAccount = nil
        client.token = nil
        UserDefaults.standard.removeObject(forKey: tokenKey)
    }
    
    func createAccount(name: String, currency: String) async throws {
        let request = CreateAccountRequest(name: name, baseCurrency: currency)
        let response: APIResponse<Account> = try await client.request(
            "accounts",
            method: "POST",
            body: request
        )
        
        // Refresh accounts list
        await fetchMe()
        
        // Set the new account as active
        activeAccount = response.data
    }
}

private struct LoginRequest: Encodable {
    let email: String
    let password: String
    let deviceName: String
}

struct RegisterRequest: Encodable {
    let name: String
    let email: String
    let password: String
    let passwordConfirmation: String
    let deviceName: String
}

struct CreateAccountRequest: Encodable {
    let name: String
    let baseCurrency: String
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

struct CreateSavingsGoalRequest: Encodable {
    let name: String
    let targetAmount: Double
    let initialAmount: Double?
    let currency: String
    let trackingMode: String
    let startDate: String
    let targetDate: String
    let categoryId: Int?
    let subcategoryId: Int?
}

struct UpdateSavingsGoalRequest: Encodable {
    let name: String
    let targetAmount: Double
    let initialAmount: Double?
    let currency: String
    let trackingMode: String
    let startDate: String
    let targetDate: String
    let categoryId: Int?
    let subcategoryId: Int?
}

struct UpdateTransactionRequest: Encodable {
    let type: String
    let amount: Double
    let currency: String
    let date: String
    let categoryId: Int?
    let subcategoryId: Int?
    let description: String?
    let paymentMethod: String?
}

struct UpdateBudgetRequest: Encodable {
    let categoryId: Int?
    let subcategoryId: Int?
    let amount: Double
    let currency: String
    let period: String
    let startDate: String
    let endDate: String?
}

struct UpdateCategoryRequest: Encodable {
    let name: String
    let type: String
    let icon: String?
    let color: String?
    let order: Int?
}

struct UpdateSubcategoryRequest: Encodable {
    let name: String
    let order: Int?
}

struct UpdateProfileRequest: Encodable {
    let name: String
    let email: String
}

struct UpdatePasswordRequest: Encodable {
    let currentPassword: String
    let password: String
    let passwordConfirmation: String
}
