<script>
    document.addEventListener('DOMContentLoaded', function () {
        const project = document.querySelector('select#project_ids');
        const categories = document.getElementById('categoryId');
        if (!project || !categories) return;
        const options = @json($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->project->name.' — '.$category->name, 'project_id' => $category->project_id])->values());
        project.addEventListener('change', function () {
            const selectedProjects = Array.from(project.selectedOptions, option => option.value);
            if (@json($defaultProjectSelection) && selectedProjects.length === 0) {
                selectedProjects.push(@json((string) \App\Models\Project::DEFAULT_ID));
            }
            const selectedCategories = Array.from(categories.selectedOptions, option => option.value);
            categories.replaceChildren();
            options.filter(option => selectedProjects.includes(String(option.project_id))).forEach(function (option) {
                categories.add(new Option(option.name, option.id, false, selectedCategories.includes(String(option.id))));
            });
        });
    });
</script>
