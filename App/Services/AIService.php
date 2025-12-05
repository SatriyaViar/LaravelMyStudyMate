<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $provider = env('AI_PROVIDER', 'openai');
        
        // Set API key based on provider
        if ($provider === 'gemini') {
            $this->apiKey = env('GEMINI_API_KEY');
            $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        } elseif ($provider === 'deepseek') {
            $this->apiKey = env('DEEPSEEK_API_KEY');
            $this->apiUrl = 'https://api.deepseek.com/v1/chat/completions';
        } else {
            $this->apiKey = env('OPENAI_API_KEY');
            $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
        }
    }

    /**
     * Generate quiz questions from study notes using AI
     * 
     * @param string $title
     * @param string $notes
     * @param int $questionCount
     * @param array $previousQuestions
     * @return array
     */
    public function generateQuiz($title, $notes, $questionCount = 5, $previousQuestions = [])
    {
        try {
            $prompt = $this->buildQuizPrompt($title, $notes, $questionCount, $previousQuestions);
            
            $provider = env('AI_PROVIDER', 'openai');
            
            if ($provider === 'gemini') {
                return $this->generateWithGemini($prompt);
            } elseif ($provider === 'deepseek') {
                return $this->generateWithDeepSeek($prompt);
            } else {
                return $this->generateWithOpenAI($prompt);
            }
        } catch (\Exception $e) {
            Log::error('AI Service Error: ' . $e->getMessage());
            throw new \Exception('Failed to generate quiz: ' . $e->getMessage());
        }
    }

    /**
     * Build prompt for quiz generation
     */
    private function buildQuizPrompt($title, $notes, $questionCount, $previousQuestions = [])
    {
        // Add randomization to ensure different questions each time
        $timestamp = time();
        $randomSeed = rand(1000, 9999);
        $variations = [
            'Focus on different aspects and scenarios than typical questions',
            'Create unique scenarios that haven\'t been commonly asked',
            'Explore edge cases and advanced implications',
            'Cover different perspectives and use cases',
            'Ask from various angles: implementation, debugging, architecture, optimization'
        ];
        $variation = $variations[array_rand($variations)];
        
        // Build previous questions warning
        $previousQuestionsText = '';
        if (!empty($previousQuestions)) {
            $previousQuestionsText = "\n\n🚫 AVOID THESE PREVIOUSLY ASKED QUESTIONS:\n";
            foreach (array_slice($previousQuestions, 0, 10) as $index => $prevQ) {
                if (!empty($prevQ)) {
                    $previousQuestionsText .= ($index + 1) . ". " . substr($prevQ, 0, 150) . "...\n";
                }
            }
            $previousQuestionsText .= "\n⚠️ Your questions MUST be COMPLETELY DIFFERENT from above!\n";
        }
        
        // Random question angles to force variety
        $angles = [
            'Performance & Optimization perspective',
            'Security & Best Practices angle', 
            'Architecture & Design Patterns view',
            'Real-world Production Scenarios',
            'Debugging & Troubleshooting focus',
            'Scalability & Maintenance concerns',
            'Edge Cases & Error Handling',
            'Industry Standards & Modern Approaches'
        ];
        $selectedAngle = $angles[array_rand($angles)];
        
        // Random contexts to create variety
        $contexts = [
            'a high-traffic e-commerce platform',
            'a healthcare application with strict compliance',
            'a real-time collaborative tool',
            'a mobile app with offline capabilities',
            'a data-intensive analytics dashboard',
            'a microservices architecture system',
            'a legacy system being modernized',
            'a startup MVP needing rapid iteration'
        ];
        $context = $contexts[array_rand($contexts)];
        
        // Random constraints
        $constraints = [
            'limited budget and tight deadline',
            'must support 1M+ concurrent users',
            'strict data privacy regulations',
            'legacy code that cannot be fully rewritten',
            'team has limited experience with technology',
            'must maintain 99.99% uptime',
            'operating in low-bandwidth environments',
            'frequent requirement changes expected'
        ];
        $constraint = $constraints[array_rand($constraints)];
        
        return "🎯 EXPERT QUIZ GENERATOR - UNIVERSITY LEVEL

TOPIC: '{$title}'
SESSION: {$timestamp}-{$randomSeed}
REQUIRED: {$questionCount} ADVANCED QUESTIONS
ANGLE: {$selectedAngle}
CONTEXT: Questions should involve scenarios in {$context}
CONSTRAINT: Consider {$constraint}
VARIATION STRATEGY: {$variation}
{$previousQuestionsText}

📖 CONTEXT (Use as starting point only, NOT limitation):
{$notes}

🔥 ABSOLUTE REQUIREMENTS - NO EXCEPTIONS:

1. **FORBIDDEN QUESTION TYPES** (Will be rejected):
   ❌ \"What is the definition of...?\"
   ❌ \"Which of the following is...?\"
   ❌ \"The main purpose of X is...?\"
   ❌ Any question answerable by simple memorization
   ❌ Questions starting with \"What is\", \"Who invented\", \"When was\"
   
2. **REQUIRED QUESTION COMPLEXITY**:
   ✅ MUST present a REAL SCENARIO with constraints
   ✅ MUST require analyzing trade-offs between options
   ✅ MUST test understanding of WHY, not just WHAT
   ✅ MUST involve decision-making in realistic context
   ✅ MUST have 2-3 viable solutions (not 1 obvious answer)

3. **SCENARIO TEMPLATES** (Pick different ones each time):
   - \"A tech lead at [Company] needs to decide between [A] and [B] for [specific case]. Given [constraints], what's optimal?\"
   - \"During code review, you notice [pattern]. The team debates [approach X] vs [approach Y]. Considering [factors], which is better?\"
   - \"Production system shows [problem]. Analysis reveals [data]. What's the root cause and solution?\"
   - \"Implementing [feature] for [users/scale]. Three approaches proposed: [A], [B], [C]. Which handles [edge case] best?\"
   - \"Legacy system uses [old way]. Modernizing to [new way]. What migration strategy minimizes [risk]?\"

4. **KNOWLEDGE EXPANSION** (Go WAY beyond notes):
   - If notes say \"Flutter basics\" → Ask about Widget lifecycle, State management patterns, Performance profiling
   - If notes say \"Database\" → Ask about Query optimization, Transaction isolation, Index strategies
   - If notes say \"API\" → Ask about Rate limiting, Caching strategies, Auth flows, Versioning
   - Assume student is preparing for PROFESSIONAL role, not just passing exam

5. **DIFFICULTY CALIBRATION**:
   🔴 HARD (40%): Requires synthesizing 3+ concepts, evaluating trade-offs, expert judgment
   🟡 MEDIUM (40%): Requires applying concepts to new situations, comparing approaches
   🟢 MEDIUM-EASY (20%): Foundational understanding with realistic complications

   For 5 questions specifically:
   - Q1: MEDIUM - Practical application with 1-2 constraints
   - Q2: HARD - Multi-factor analysis with competing concerns  
   - Q3: MEDIUM - Comparison requiring deep understanding
   - Q4: HARD - Complex debugging or optimization problem
   - Q5: HARD - Architecture decision with long-term implications

6. **DISTRACTOR ENGINEERING** (Critical for difficulty):
   All 4 options MUST be:
   - Technically plausible (not obviously wrong)
   - Sound correct to intermediate students
   - Based on common misconceptions or partial truths
   - Different enough to require analysis
   
   Example GOOD set:
   A) Correct in theory but fails at scale (10k+ users)
   B) Popular approach but has hidden security flaw
   C) ✓ BEST - Optimal balance of all factors
   D) Works perfectly but violates industry standards
   
   Example BAD set (FORBIDDEN):
   A) Completely impossible solution
   B) Joke answer or obviously wrong
   C) ✓ Only correct answer
   D) Contradicts basic facts
   
3. **Question Coverage (Go BEYOND notes - VARY TOPICS EACH TIME):**
   - Industry Best Practices (even if not in notes)
   - Advanced Patterns and Architectures  
   - Performance Optimization Strategies
   - Security Considerations
   - Scalability and Maintenance
   - Real Production Challenges
   - Modern Tools and Frameworks
   - Expert-level Decision Making
   
   ⚠️ IMPORTANT: Don't ask the same aspects every time!
   - If previous quiz focused on performance, now focus on security or architecture
   - Rotate between different knowledge areas
   - Cover breadth AND depth of the topic
   
4. **Question Types (Mix These - VARY EACH QUIZ):**
   - Real-world Scenarios: Enterprise-level problems
   - Architecture Decisions: System design choices
   - Performance Analysis: Bottleneck identification
   - Code Review: Spot issues in complex scenarios
   - Best Practice Evaluation: When to use what
   - Debugging Complex Issues: Root cause analysis

5. **Topic Expansion Strategy:**
   IF notes mention \"Flutter basics\" → Ask about Flutter architecture, state management, performance
   IF notes mention \"Database\" → Ask about indexing, normalization, query optimization, transactions
   IF notes mention \"API\" → Ask about RESTful design, authentication, rate limiting, caching
   IF notes mention \"Security\" → Ask about OWASP Top 10, encryption, authentication flows
   
   💡 The notes are a STARTING POINT - expand to full professional-level knowledge!

6. **CRITICAL: Distractor (Wrong Answer) Rules:**
   - ALL 4 options must sound CORRECT at first glance
   - Use SUBTLE differences between options
   - Include answers that are \"partially correct\" but not optimal
   - Each wrong answer should appeal to different misconceptions
   - NO obviously wrong/silly options
   - Make students doubt their answer and reconsider
   
   Example of GOOD distractors:
   ✅ Option A: Correct in theory but fails in edge case
   ✅ Option B: Common practice but not best practice  
   ✅ Option C: CORRECT - Best solution overall
   ✅ Option D: Works but has hidden drawback
   
   Example of BAD distractors (AVOID):
   ❌ Option A: Completely unrelated answer
   ❌ Option B: Obviously impossible solution
   ❌ Option C: Joke answer
   ❌ Option D: Too simple/basic

7. **Question Complexity:**
   - Use multi-step reasoning (requires 2-3 mental steps)
   - Include constraints that eliminate obvious choices
   - Test understanding of trade-offs and edge cases
   - Require weighing pros/cons of multiple valid approaches
   - Make correct answer not immediately obvious

8. **Answer Crafting Strategy:**
   - Write 4 options that ALL could theoretically work
   - Then add subtle reason why 3 are suboptimal
   - Correct answer should be \"best\" not \"only\" solution
   - Force students to analyze WHY one is better than others

Generate exactly {$questionCount} questions in JSON format:
[
  {
    \"question\": \"[Complex scenario with specific constraints and context]\",
    \"options\": [
      \"[Option that works but has performance trade-off]\",
      \"[Popular approach but not optimal for this case]\",
      \"[Correct - Best balance of all factors]\",
      \"[Technically correct but violates best practices]\"
    ],
    \"correct_answer\": 2,
    \"explanation\": \"[Detailed: Why option 3 is BEST + Why option 1 fails in X scenario + Why option 2 is suboptimal + Why option 4 has hidden issue + Core principle explanation]\"
  }
]

📊 MANDATORY VARIETY RULES:

For EACH question in this quiz:
1. Use DIFFERENT scenario type (don't repeat same format)
2. Test DIFFERENT aspect of topic (performance vs security vs architecture)
3. Use DIFFERENT companies/contexts (startup vs enterprise vs legacy system)
4. Focus on DIFFERENT skill level (Q1: application, Q2: analysis, Q3: evaluation, Q4: synthesis, Q5: creation)

🎯 SELF-VALIDATION (You MUST pass all checks):
- [ ] Zero questions start with \"What is\" or \"Which of the following\"
- [ ] Every question has a realistic scenario with constraints
- [ ] All 4 options sound correct to someone with surface knowledge
- [ ] Correct answer is \"best\" not \"only\" solution
- [ ] At least 2 options are partially correct in different contexts
- [ ] Explanation covers WHY each wrong answer fails in THIS scenario
- [ ] Questions cover 5 DIFFERENT aspects of '{$title}'
- [ ] No two questions test the same knowledge point
- [ ] Reading notes alone would NOT be enough to answer confidently
- [ ] Would take experienced developer 30+ seconds per question

⚠️ DIFFICULTY TEST: If you think \"this is too hard\" → It's probably right difficulty!

🚨 FINAL WARNING: If I see \"What is...\", \"Define...\", or simple factual questions, START OVER!

Return ONLY valid JSON array, no markdown code blocks or additional text.";
    }

    /**
     * Generate quiz using OpenAI
     */
    private function generateWithOpenAI($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->apiUrl, [
            'model' => env('OPENAI_MODEL', 'gpt-4'),  // Use GPT-4 for better quality
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert university-level quiz creator specializing in challenging, scenario-based questions that test deep understanding. Always create varied, non-repetitive questions.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 1.1,
            'max_tokens' => 8000,
            'presence_penalty' => 0.6,  // Discourage repetition
            'frequency_penalty' => 0.3,  // Reduce repeating patterns
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        return $this->parseQuizResponse($content);
    }

    /**
     * Generate quiz using Google Gemini
     */
    private function generateWithGemini($prompt)
    {
        $response = Http::timeout(60)->post($this->apiUrl . '?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 1.2,  // Higher = more creative and varied
                'maxOutputTokens' => 8000,  // More space for complex questions
                'topP' => 0.95,  // Nucleus sampling for diversity
                'topK' => 64,  // Increased for more variety
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        return $this->parseQuizResponse($content);
    }

    /**
     * Generate quiz using DeepSeek
     */
    private function generateWithDeepSeek($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(90)->post($this->apiUrl, [
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert university-level quiz creator specializing in challenging, scenario-based questions that test deep understanding. Always create varied, non-repetitive questions. Return ONLY valid JSON format.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 1.2,
            'max_tokens' => 8000,
            'top_p' => 0.95,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.5,
        ]);

        if (!$response->successful()) {
            throw new \Exception('DeepSeek API error: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        return $this->parseQuizResponse($content);
    }

    /**
     * Parse AI response and extract JSON
     */
    private function parseQuizResponse($content)
    {
        // Remove markdown code blocks if present
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = trim($content);

        // Try to find JSON array
        if (preg_match('/\[\s*\{.*?\}\s*\]/s', $content, $matches)) {
            $content = $matches[0];
        }

        $questions = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }

        if (!is_array($questions)) {
            throw new \Exception('AI response is not an array');
        }

        // Validate question structure
        foreach ($questions as $index => $question) {
            if (!isset($question['question']) || !isset($question['options']) || !isset($question['correct_answer'])) {
                throw new \Exception("Invalid question structure at index {$index}");
            }

            if (!is_array($question['options']) || count($question['options']) < 2) {
                throw new \Exception("Invalid options at index {$index}");
            }
        }

        return $questions;
    }

    /**
     * Generate fallback quiz if AI fails
     */
    public function generateFallbackQuiz($title, $notes)
    {
        $noteExcerpt = substr($notes, 0, 100);
        
        return [
            [
                'question' => "Based on your understanding of {$title}, which approach would be most effective in a production environment?",
                'options' => [
                    "Focus only on basic implementation without considering edge cases",
                    "Implement with comprehensive error handling and scalability in mind",
                    "Use the simplest solution without documentation",
                    "Copy solutions from tutorials without understanding"
                ],
                'correct_answer' => 1,
                'explanation' => "Production systems require comprehensive error handling, scalability considerations, and proper documentation to ensure long-term maintainability and reliability."
            ],
            [
                'question' => "When implementing {$title}, what is the most critical factor to consider first?",
                'options' => [
                    "Code aesthetics and formatting",
                    "System architecture and design patterns",
                    "Using the latest trending libraries",
                    "Writing code as quickly as possible"
                ],
                'correct_answer' => 1,
                'explanation' => "System architecture and design patterns should be considered first as they form the foundation that affects all subsequent implementation decisions and long-term maintainability."
            ],
            [
                'question' => "In the context of {$title}, how should you handle performance optimization?",
                'options' => [
                    "Optimize everything from the start even without bottlenecks",
                    "Profile first, identify bottlenecks, then optimize strategically",
                    "Never optimize, it's premature optimization",
                    "Only optimize when users complain"
                ],
                'correct_answer' => 1,
                'explanation' => "The best practice is to profile first to identify actual bottlenecks, then optimize strategically based on data. This prevents wasted effort on non-issues while addressing real performance problems."
            ],
            [
                'question' => "What testing strategy is most appropriate for {$title}?",
                'options' => [
                    "Only manual testing when features are complete",
                    "Comprehensive unit, integration, and end-to-end testing",
                    "No testing, just deploy and fix bugs later",
                    "Only test the happy path scenarios"
                ],
                'correct_answer' => 1,
                'explanation' => "A comprehensive testing strategy with unit tests, integration tests, and end-to-end tests provides the best coverage and catches issues at different levels, reducing bugs in production."
            ],
            [
                'question' => "When maintaining code related to {$title}, what principle should guide your decisions?",
                'options' => [
                    "Always rewrite everything with new technologies",
                    "Balance between technical debt, business needs, and code quality",
                    "Never change working code regardless of quality",
                    "Prioritize speed over all other considerations"
                ],
                'correct_answer' => 1,
                'explanation' => "Effective code maintenance requires balancing technical debt management with business requirements and code quality. This pragmatic approach ensures sustainable development while meeting business objectives."
            ]
        ];
    }
}
