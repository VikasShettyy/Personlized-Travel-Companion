## 🚀 Getting Started

### 1. Install Git

Download Git from https://git-scm.com/downloads.

Verify the installation:

```bash
git --version
```

### 2. Clone the Repository

Open your terminal and run:

```bash
git clone https://github.com/YOUR-USERNAME/Personlized-Travel-Companion.git
```

Replace `YOUR-USERNAME` with the GitHub username or organization that owns the repository.

### 3. Open the Project Folder

```bash
cd Personlized-Travel-Companion
```

### 4. Create Your Own Branch

**Every team member must create a separate branch before making any changes. Do not work directly on the `main` branch.**

First, get the latest changes:

```bash
git switch main
git pull origin main
```

Create and switch to your own branch:

```bash
git switch -c feature/your-name
```

For example:

```bash
git switch -c feature/something
```

Use a meaningful branch name based on your name or assigned task.

Examples:
- `feature/login-page`
- `feature/homepage`
- `feature/travel-search`
- `fix/navbar-bug`

If you are starting another task later, create a new branch from the updated `main` branch.

## 🔄 Development Workflow

Follow these steps every time you work on the project.

### Step 1: Update Your Branch

Before starting a new task, make sure your branch is up to date with the latest `main` branch.

```bash
git switch main
git pull origin main
git switch feature/your-name
git merge main
```

### Step 2: Make Your Changes

Add or edit the files assigned to you using Visual Studio Code or your preferred editor.

### Step 3: Check Your Changes

```bash
git status
```

### Step 4: Stage Your Files

Stage all your changes:

```bash
git add .
```

Or stage a specific file:

```bash
git add filename.html
```

### Step 5: Commit Your Changes

```bash
git commit -m "Add travel search page"
```

Write a meaningful message describing what you changed.

### Step 6: Push Your Branch to GitHub

```bash
git push -u origin feature/your-name
```

For subsequent pushes to the same branch, use:

```bash
git push
```

### Step 7: Create a Pull Request

After pushing your branch:

1. Open the repository on GitHub.
2. Click **Compare & pull request**, or open the **Pull requests** tab and create a new pull request.
3. Set the **base branch** to `main`.
4. Set the **compare branch** to your feature branch.
5. Describe your changes and submit the pull request.
6. Ask a teammate to review your code.
7. Merge the pull request into `main` after review and approval.

Once the changes are merged, everyone can download them by running:

```bash
git switch main
git pull origin main
```

## 👥 Team Collaboration Rules

1. **Never work directly on `main`.** Create your own branch for every task.
2. **Pull the latest changes** before starting new work.
3. **Use meaningful branch names** that describe your name or task.
4. **Commit frequently** with clear commit messages.
5. **Push changes to your own branch**, not another team member's branch.
6. **Create a pull request** to merge your work into `main`.
7. **Review code before merging** to prevent bugs and accidental overwrites.
8. **Communicate with your teammates** if you need to modify shared files.
9. **Never commit passwords, API keys, or database credentials.**


All team members should follow this workflow to keep the `main` branch stable and the project organized.
