import os

# ===== AUTO PROJECT ROOT =====
PROJECT_ROOT = os.getcwd()  # current directory
OUTPUT_FILE = "HireFlow_Relevant_Code.txt"

INCLUDE_DIRS = [
    "src/Controller",
    "src/Entity",
    "src/Form",
    "src/Repository",
    "src/Security",
    "migrations",
    "templates"
]

ALLOWED_EXTENSIONS = (".php", ".twig")


def write_separator(file, title):
    file.write("\n" + "=" * 90 + "\n")
    file.write(title + "\n")
    file.write("=" * 90 + "\n\n")


def export_code():
    found_any_file = False

    with open(OUTPUT_FILE, "w", encoding="utf-8") as output:
        output.write("HIRE FLOW – RELEVANT SOURCE CODE EXPORT\n")
        output.write("Generated automatically by Python script\n")
        output.write("=" * 90 + "\n\n")

        for folder in INCLUDE_DIRS:
            folder_path = os.path.join(PROJECT_ROOT, folder)

            if not os.path.exists(folder_path):
                continue

            write_separator(output, f"FOLDER: {folder}")

            for root, _, files in os.walk(folder_path):
                for file in files:
                    if file.endswith(ALLOWED_EXTENSIONS):
                        found_any_file = True

                        full_path = os.path.join(root, file)
                        relative_path = os.path.relpath(full_path, PROJECT_ROOT)

                        write_separator(output, f"FILE: {relative_path}")

                        with open(full_path, "r", encoding="utf-8") as f:
                            output.write(f.read() + "\n")

        if not found_any_file:
            output.write("⚠️ No files were found. Check folder structure.\n")

    print(f"✅ Export finished → {OUTPUT_FILE}")


if __name__ == "__main__":
    export_code()
