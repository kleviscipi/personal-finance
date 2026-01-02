import SwiftUI

struct ProfileView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    @State private var name: String = ""
    @State private var email: String = ""
    @State private var currentPassword: String = ""
    @State private var newPassword: String = ""
    @State private var confirmPassword: String = ""
    
    @State private var isUpdatingProfile = false
    @State private var isUpdatingPassword = false
    @State private var profileErrorMessage: String?
    @State private var passwordErrorMessage: String?
    @State private var showingDeleteAlert = false
    @State private var showingPasswordSection = false
    @State private var successMessage: String?
    
    var body: some View {
        NavigationStack {
            Form {
                Section("Profile Information") {
                    TextField("Name", text: $name)
                    TextField("Email", text: $email)
                        .keyboardType(.emailAddress)
                        .textInputAutocapitalization(.never)
                    
                    if let error = profileErrorMessage {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                    
                    if let success = successMessage {
                        Text(success)
                            .foregroundStyle(.green)
                            .font(.caption)
                    }
                    
                    Button {
                        Task {
                            await updateProfile()
                        }
                    } label: {
                        if isUpdatingProfile {
                            ProgressView()
                        } else {
                            Text("Update Profile")
                        }
                    }
                    .disabled(isUpdatingProfile || !isProfileValid)
                }
                
                Section {
                    Button {
                        showingPasswordSection.toggle()
                    } label: {
                        HStack {
                            Text("Change Password")
                            Spacer()
                            Image(systemName: showingPasswordSection ? "chevron.up" : "chevron.down")
                        }
                    }
                    
                    if showingPasswordSection {
                        SecureField("Current Password", text: $currentPassword)
                        SecureField("New Password", text: $newPassword)
                        SecureField("Confirm Password", text: $confirmPassword)
                        
                        if let error = passwordErrorMessage {
                            Text(error)
                                .foregroundStyle(.red)
                                .font(.caption)
                        }
                        
                        Button {
                            Task {
                                await updatePassword()
                            }
                        } label: {
                            if isUpdatingPassword {
                                ProgressView()
                            } else {
                                Text("Update Password")
                            }
                        }
                        .disabled(isUpdatingPassword || !isPasswordValid)
                    }
                }
                
                Section {
                    Button(role: .destructive) {
                        showingDeleteAlert = true
                    } label: {
                        Text("Delete Account")
                    }
                }
            }
            .navigationTitle("Profile")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Close") {
                        dismiss()
                    }
                }
            }
            .alert("Delete Account", isPresented: $showingDeleteAlert) {
                Button("Cancel", role: .cancel) {}
                Button("Delete", role: .destructive) {
                    Task {
                        await deleteAccount()
                    }
                }
            } message: {
                Text("Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.")
            }
            .task {
                if let user = appState.user {
                    name = user.name
                    email = user.email
                }
            }
        }
    }
    
    private var isProfileValid: Bool {
        !name.isEmpty && !email.isEmpty && ValidationUtils.isValidEmail(email)
    }
    
    private var isPasswordValid: Bool {
        !currentPassword.isEmpty && 
        !newPassword.isEmpty && 
        newPassword.count >= 8 &&
        newPassword == confirmPassword
    }
    
    private func updateProfile() async {
        guard isProfileValid else { return }
        
        isUpdatingProfile = true
        profileErrorMessage = nil
        successMessage = nil
        defer { isUpdatingProfile = false }
        
        do {
            try await appState.updateProfile(name: name, email: email)
            successMessage = "Profile updated successfully"
        } catch {
            profileErrorMessage = error.localizedDescription
        }
    }
    
    private func updatePassword() async {
        guard isPasswordValid else { return }
        
        isUpdatingPassword = true
        passwordErrorMessage = nil
        defer { isUpdatingPassword = false }
        
        do {
            try await appState.updatePassword(
                currentPassword: currentPassword,
                newPassword: newPassword
            )
            
            // Clear password fields
            currentPassword = ""
            newPassword = ""
            confirmPassword = ""
            showingPasswordSection = false
            successMessage = "Password updated successfully"
        } catch {
            passwordErrorMessage = error.localizedDescription
        }
    }
    
    private func deleteAccount() async {
        do {
            try await appState.deleteAccount()
            // Logout will be handled automatically by the state
        } catch {
            profileErrorMessage = error.localizedDescription
        }
    }
}
