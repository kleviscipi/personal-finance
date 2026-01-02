import SwiftUI

struct FamilyView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    
    @State private var members: [FamilyMember] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showingInviteSheet = false
    @State private var memberToDelete: FamilyMember?
    @State private var showingDeleteAlert = false
    
    @State private var inviteEmail = ""
    @State private var inviteRole = "member"
    @State private var isInviting = false
    @State private var inviteError: String?
    
    let roles = [
        ("owner", "Owner"),
        ("admin", "Admin"),
        ("member", "Member"),
        ("viewer", "Viewer")
    ]
    
    var body: some View {
        NavigationStack {
            List {
                Section {
                    ForEach(members) { member in
                        VStack(alignment: .leading, spacing: 4) {
                            HStack {
                                VStack(alignment: .leading) {
                                    Text(member.name)
                                        .font(.headline)
                                    Text(member.email)
                                        .font(.caption)
                                        .foregroundStyle(.secondary)
                                }
                                Spacer()
                                VStack(alignment: .trailing) {
                                    Text(member.role.capitalized)
                                        .font(.caption)
                                        .padding(.horizontal, 8)
                                        .padding(.vertical, 2)
                                        .background(roleColor(member.role))
                                        .foregroundColor(.white)
                                        .cornerRadius(4)
                                    if !member.isActive {
                                        Text("Inactive")
                                            .font(.caption2)
                                            .foregroundStyle(.secondary)
                                    }
                                }
                            }
                        }
                        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
                            if member.role != "owner" {
                                Button(role: .destructive) {
                                    memberToDelete = member
                                    showingDeleteAlert = true
                                } label: {
                                    Label("Remove", systemImage: "person.badge.minus")
                                }
                            }
                        }
                    }
                } header: {
                    Text("Family Members")
                }
            }
            .overlay {
                if isLoading {
                    ProgressView("Loading members...")
                } else if members.isEmpty {
                    ContentUnavailableView(
                        "No Members Yet",
                        systemImage: "person.2",
                        description: Text("Invite family members to collaborate")
                    )
                }
            }
            .navigationTitle("Family & Members")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Close") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .primaryAction) {
                    Button {
                        showingInviteSheet = true
                    } label: {
                        Label("Invite", systemImage: "person.badge.plus")
                    }
                }
            }
            .task {
                await loadMembers()
            }
            .sheet(isPresented: $showingInviteSheet) {
                inviteMemberSheet
            }
            .alert("Remove Member", isPresented: $showingDeleteAlert, presenting: memberToDelete) { member in
                Button("Cancel", role: .cancel) {
                    memberToDelete = nil
                }
                Button("Remove", role: .destructive) {
                    Task {
                        await removeMember(member)
                    }
                }
            } message: { member in
                Text("Are you sure you want to remove \(member.name) from the account?")
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
    
    private var inviteMemberSheet: some View {
        NavigationStack {
            Form {
                Section("Invite Details") {
                    TextField("Email", text: $inviteEmail)
                        .keyboardType(.emailAddress)
                        .textInputAutocapitalization(.never)
                    
                    Picker("Role", selection: $inviteRole) {
                        ForEach(roles.filter { $0.0 != "owner" }, id: \.0) { role in
                            Text(role.1).tag(role.0)
                        }
                    }
                }
                
                if let error = inviteError {
                    Section {
                        Text(error)
                            .foregroundStyle(.red)
                            .font(.caption)
                    }
                }
            }
            .navigationTitle("Invite Member")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        showingInviteSheet = false
                        inviteEmail = ""
                        inviteRole = "member"
                        inviteError = nil
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Send") {
                        Task {
                            await inviteMember()
                        }
                    }
                    .disabled(isInviting || !isInviteValid)
                }
            }
        }
    }
    
    private var isInviteValid: Bool {
        !inviteEmail.isEmpty && inviteEmail.contains("@")
    }
    
    private func roleColor(_ role: String) -> Color {
        switch role {
        case "owner": return .purple
        case "admin": return .blue
        case "member": return .green
        case "viewer": return .gray
        default: return .gray
        }
    }
    
    private func loadMembers() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        
        do {
            members = try await appState.fetchFamilyMembers()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
    
    private func inviteMember() async {
        guard isInviteValid else { return }
        
        isInviting = true
        inviteError = nil
        defer { isInviting = false }
        
        do {
            try await appState.inviteFamilyMember(email: inviteEmail, role: inviteRole)
            showingInviteSheet = false
            inviteEmail = ""
            inviteRole = "member"
            await loadMembers()
        } catch {
            inviteError = error.localizedDescription
        }
    }
    
    private func removeMember(_ member: FamilyMember) async {
        do {
            try await appState.removeFamilyMember(member.id)
            members.removeAll { $0.id == member.id }
            memberToDelete = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
