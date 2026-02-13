<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_workflows_index()
    {
        $user = User::factory()->create();
        Workflow::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/workflows');

        $response->assertStatus(200);
    }

    public function test_can_create_workflow()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Workflow',
            'type' => 'general',
            'description' => 'Test description',
            'steps' => [
                ['name' => 'Step 1', 'requires_approval' => false],
                ['name' => 'Step 2', 'requires_approval' => true, 'approvers' => [$user->id]],
            ],
        ];

        $response = $this->actingAs($user)->post('/workflows', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('workflows', [
            'name' => 'Test Workflow',
            'type' => 'general',
        ]);
    }

    public function test_can_view_single_workflow()
    {
        $user = User::factory()->create();
        $workflow = Workflow::factory()->create();

        $response = $this->actingAs($user)->get("/workflows/{$workflow->id}");

        $response->assertStatus(200);
    }
}