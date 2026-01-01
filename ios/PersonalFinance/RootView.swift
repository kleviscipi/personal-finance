import SwiftUI

struct RootView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        Group {
            if appState.token == nil {
                LoginView()
            } else if appState.activeAccount == nil {
                AccountPickerView()
            } else {
                MainTabView()
            }
        }
        .task {
            if appState.token != nil && appState.user == nil {
                await appState.fetchMe()
            }
        }
    }
}
