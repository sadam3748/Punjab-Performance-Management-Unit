<?php
namespace App\Http\Controllers;

use App\Models\KpiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class KpiCategoryController extends Controller
{
    /**
     * Display KPI category list with filters.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ];

        $query = KpiCategory::query();

        // Search by name, code, or description
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Filter active/inactive
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            }

            if ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $kpiCategories = $query
            ->when(Schema::hasColumn('kpi_categories', 'sort_order'), function ($q) {
                $q->orderBy('sort_order');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total_categories'    => KpiCategory::count(),
            'active_categories'   => KpiCategory::where('is_active', true)->count(),
            'inactive_categories' => KpiCategory::where('is_active', false)->count(),
            'used_categories'     => KpiCategory::where('is_active', true)->count(),
        ];

        return view('kpi.index', compact(
            'kpiCategories',
            'summary',
            'filters'
        ));
    }

    /**
     * Show create KPI category form.
     */
    public function create()
    {
        return view('kpi.create');
    }

    /**
     * Store new KPI category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:kpi_categories,name'],
            'code'        => ['nullable', 'string', 'max:50', 'unique:kpi_categories,code'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['required', 'boolean'],
            'icon'        => ['nullable', 'string', 'max:100'],
        ]);

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::slug($validated['name'], '_'));
        }

        $data = [
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'],
            'icon'        => $validated['icon'] ?? null,
        ];

        if (Schema::hasColumn('kpi_categories', 'sort_order')) {
            $data['sort_order'] = $validated['sort_order'] ?? 0;
        }

        KpiCategory::create($data);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI category created successfully.');
    }

    /**
     * Show edit KPI category form.
     */
    public function edit(KpiCategory $kpiCategory)
    {
        return view('kpi.edit', compact('kpiCategory'));
    }

    /**
     * Update KPI category.
     */
    public function update(Request $request, KpiCategory $kpiCategory)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:kpi_categories,name,' . $kpiCategory->id],
            'code'        => ['nullable', 'string', 'max:50', 'unique:kpi_categories,code,' . $kpiCategory->id],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['required', 'boolean'],
            'icon'        => ['nullable', 'string', 'max:100'],
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::slug($validated['name'], '_'));
        }

        $data = [
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'],
            'icon'        => $validated['icon'] ?? null,
        ];

        if (Schema::hasColumn('kpi_categories', 'sort_order')) {
            $data['sort_order'] = $validated['sort_order'] ?? 0;
        }

        $kpiCategory->update($data);

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI category updated successfully.');
    }

    /**
     * Delete KPI category.
     *
     * Note: In future, add protection if category is used in inspections/baseline data.
     */
    public function destroy(KpiCategory $kpiCategory)
    {
        $kpiCategory->delete();

        return redirect()
            ->route('kpi.index')
            ->with('success', 'KPI category deleted successfully.');
    }
}
