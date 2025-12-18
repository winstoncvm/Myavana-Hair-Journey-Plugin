/**
 * Myavana Hair Journey Timeline - Sample Data
 *
 * This file contains sample data for previewing the timeline wireframe
 */

const MYAVANA_SAMPLE_DATA = {
    user: {
        name: "Sarah",
        streak: 7,
        stats: {
            totalEntries: 24,
            activeGoals: 3,
            healthScore: 8.2,
            routineSteps: 5
        }
    },

    goals: [
        {
            id: 1,
            title: "Length Growth",
            category: "growth",
            startDate: "2025-02-01",
            targetDate: "2025-04-30",
            progress: 75,
            milestones: [
                { threshold: 25, achieved: true, date: "2025-02-15" },
                { threshold: 50, achieved: true, date: "2025-03-01" },
                { threshold: 75, achieved: true, date: "2025-03-15" },
                { threshold: 100, achieved: false }
            ],
            linkedEntries: [1, 5, 8, 12],
            aiInsights: {
                prediction: "On track to complete by target date",
                confidence: 0.85,
                recommendations: [
                    "Continue deep conditioning weekly",
                    "Protect ends during styling",
                    "Consider adding protein treatment"
                ]
            }
        },
        {
            id: 2,
            title: "Moisture Balance",
            category: "hydration",
            startDate: "2025-01-15",
            targetDate: "2025-03-15",
            progress: 60,
            milestones: [
                { threshold: 25, achieved: true, date: "2025-01-30" },
                { threshold: 50, achieved: true, date: "2025-02-15" },
                { threshold: 75, achieved: false },
                { threshold: 100, achieved: false }
            ],
            linkedEntries: [2, 6, 10, 15],
            aiInsights: {
                prediction: "Slightly behind schedule",
                confidence: 0.75,
                recommendations: [
                    "Increase leave-in conditioner frequency",
                    "Seal moisture with oil after each wash",
                    "Try overnight deep conditioning"
                ]
            }
        },
        {
            id: 3,
            title: "Color Protection",
            category: "maintenance",
            startDate: "2025-03-01",
            targetDate: "2025-05-20",
            progress: 40,
            milestones: [
                { threshold: 25, achieved: true, date: "2025-03-10" },
                { threshold: 50, achieved: false },
                { threshold: 75, achieved: false },
                { threshold: 100, achieved: false }
            ],
            linkedEntries: [3, 7, 11],
            aiInsights: {
                prediction: "On track",
                confidence: 0.80,
                recommendations: [
                    "Use UV protection spray before sun exposure",
                    "Reduce heat styling frequency",
                    "Try color-safe deep conditioner"
                ]
            }
        }
    ],

    entries: [
        {
            id: 1,
            type: "entry",
            date: "2025-03-15",
            time: "14:30",
            title: "Wash Day Success! 💧",
            description: "Finally perfected my wash day routine. Hair feels so soft and moisturized. Used the new deep conditioner and it made a huge difference!",
            images: [
                "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect fill='%23e7a690' width='400' height='400'/><circle cx='200' cy='200' r='100' fill='%23fce5d7'/></svg>"
            ],
            healthRating: 9,
            products: ["Shampoo", "Deep Conditioner", "Leave-In", "Hair Oil"],
            mood: "Excited",
            tags: ["wash-day", "moisture", "success"],
            aiAnalysis: {
                curlPattern: "3B",
                healthScore: 92,
                hydration: 88,
                elasticity: 85,
                recommendations: [
                    "Excellent moisture retention",
                    "Curl definition improved significantly",
                    "Consider adding protein treatment next week"
                ]
            },
            comments: 5,
            linkedGoals: [1, 2]
        },
        {
            id: 2,
            type: "entry",
            date: "2025-03-12",
            time: "09:00",
            title: "Morning Routine",
            description: "Quick refresh with water and leave-in conditioner. Curls bounced back nicely!",
            images: [
                "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect fill='%23fce5d7' width='400' height='400'/><circle cx='200' cy='200' r='80' fill='%23e7a690'/></svg>"
            ],
            healthRating: 8,
            products: ["Leave-In Conditioner", "Curl Cream"],
            mood: "Happy",
            tags: ["refresh", "morning"],
            linkedGoals: [2]
        },
        {
            id: 3,
            type: "entry",
            date: "2025-03-10",
            time: "19:00",
            title: "Trim Session ✂️",
            description: "Got my ends trimmed today. Lost about 1 inch but hair looks so much healthier!",
            images: [
                "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 400'><rect fill='%23eeece1' width='400' height='400'/><path d='M200 100 L300 300 L100 300 Z' fill='%23e7a690'/></svg>"
            ],
            healthRating: 10,
            products: [],
            mood: "Satisfied",
            tags: ["trim", "health", "salon"],
            linkedGoals: [1, 3]
        }
    ],

    routines: [
        {
            id: 1,
            type: "routine",
            name: "Morning Moisturize",
            icon: "☀️",
            frequency: "daily",
            time: "08:00",
            steps: [
                { order: 1, action: "Spray with water", duration: 2 },
                { order: 2, action: "Apply leave-in conditioner", duration: 5 },
                { order: 3, action: "Seal with oil", duration: 3 },
                { order: 4, action: "Style as desired", duration: 10 }
            ],
            products: ["Water Spray", "Leave-In Conditioner", "Hair Oil", "Styling Gel"],
            completionHistory: [
                { date: "2025-03-15", completed: true },
                { date: "2025-03-14", completed: true },
                { date: "2025-03-13", completed: false, reason: "Skipped - late morning" },
                { date: "2025-03-12", completed: true }
            ],
            linkedGoals: [2],
            effectiveness: {
                consistencyRate: 0.85,
                avgHealthImpact: 0.7
            }
        },
        {
            id: 2,
            type: "routine",
            name: "Weekly Deep Condition",
            icon: "💧",
            frequency: "weekly",
            time: "14:00",
            steps: [
                { order: 1, action: "Shampoo hair", duration: 10 },
                { order: 2, action: "Apply deep conditioner", duration: 5 },
                { order: 3, action: "Steam or heat cap", duration: 30 },
                { order: 4, action: "Rinse and style", duration: 15 }
            ],
            products: ["Clarifying Shampoo", "Deep Conditioner", "Leave-In"],
            completionHistory: [
                { date: "2025-03-15", completed: true },
                { date: "2025-03-08", completed: true },
                { date: "2025-03-01", completed: true }
            ],
            linkedGoals: [1, 2],
            effectiveness: {
                consistencyRate: 0.95,
                avgHealthImpact: 0.9
            }
        },
        {
            id: 3,
            type: "routine",
            name: "Night Protection",
            icon: "🌙",
            frequency: "daily",
            time: "22:00",
            steps: [
                { order: 1, action: "Pineapple or loose bun", duration: 5 },
                { order: 2, action: "Apply light oil to ends", duration: 3 },
                { order: 3, action: "Satin bonnet or pillowcase", duration: 2 }
            ],
            products: ["Hair Oil", "Satin Bonnet"],
            completionHistory: [
                { date: "2025-03-15", completed: true },
                { date: "2025-03-14", completed: true },
                { date: "2025-03-13", completed: true }
            ],
            linkedGoals: [1, 3],
            effectiveness: {
                consistencyRate: 0.90,
                avgHealthImpact: 0.8
            }
        }
    ],

    aiInsights: [
        {
            type: "progress",
            message: "Great momentum on Length Growth! You're 15% ahead of schedule.",
            priority: "high",
            timestamp: "2025-03-15T10:00:00Z"
        },
        {
            type: "recommendation",
            message: "Try adding protein treatments weekly to boost elasticity.",
            priority: "medium",
            timestamp: "2025-03-14T12:00:00Z"
        },
        {
            type: "milestone",
            message: "🎉 You're 5 days away from your 75% milestone!",
            priority: "high",
            timestamp: "2025-03-13T09:00:00Z"
        },
        {
            type: "warning",
            message: "No entries logged in the past 3 days. Stay consistent!",
            priority: "medium",
            timestamp: "2025-03-10T08:00:00Z"
        }
    ],

    // Calendar view data (for project-style grid)
    calendarEvents: {
        "2025-03-15": [
            { type: "entry", id: 1, time: "14:30", title: "Wash Day Success!" },
            { type: "routine", id: 1, time: "08:00", title: "Morning Moisturize" },
            { type: "routine", id: 3, time: "22:00", title: "Night Protection" }
        ],
        "2025-03-14": [
            { type: "routine", id: 1, time: "08:00", title: "Morning Moisturize" },
            { type: "routine", id: 3, time: "22:00", title: "Night Protection" }
        ],
        "2025-03-12": [
            { type: "entry", id: 2, time: "09:00", title: "Morning Routine" },
            { type: "routine", id: 1, time: "08:00", title: "Morning Moisturize" }
        ],
        "2025-03-10": [
            { type: "entry", id: 3, time: "19:00", title: "Trim Session" }
        ]
    }
};

// Helper functions for data access
const DataHelpers = {
    getGoalById: (id) => MYAVANA_SAMPLE_DATA.goals.find(g => g.id === id),
    getEntryById: (id) => MYAVANA_SAMPLE_DATA.entries.find(e => e.id === id),
    getRoutineById: (id) => MYAVANA_SAMPLE_DATA.routines.find(r => r.id === id),

    getEntriesByDateRange: (startDate, endDate) => {
        return MYAVANA_SAMPLE_DATA.entries.filter(e => {
            const entryDate = new Date(e.date);
            return entryDate >= new Date(startDate) && entryDate <= new Date(endDate);
        });
    },

    getActiveGoals: () => {
        return MYAVANA_SAMPLE_DATA.goals.filter(g => g.progress < 100);
    },

    getCompletedGoals: () => {
        return MYAVANA_SAMPLE_DATA.goals.filter(g => g.progress === 100);
    },

    getCalendarEventsForDate: (date) => {
        return MYAVANA_SAMPLE_DATA.calendarEvents[date] || [];
    },

    getTodayEvents: () => {
        const today = new Date().toISOString().split('T')[0];
        return DataHelpers.getCalendarEventsForDate(today);
    }
};

// Export for use in timeline.js
if (typeof window !== 'undefined') {
    window.MYAVANA_DATA = MYAVANA_SAMPLE_DATA;
    window.MYAVANA_HELPERS = DataHelpers;
}
