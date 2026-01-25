export type RuleCondition = {
    id: string;
    condition_type: string;
    operator: string;
    value: string;
    values: string[];
    time: {
        timezone: string;
        days: string[];
        hours: {
            start: number | null;
            end: number | null;
        };
    };
};

export type Rule = {
    id: string;
    priority: number;
    destination_url: string;
    enabled: boolean;
    conditions: RuleCondition[];
};

let ruleCounter = 0;
let conditionCounter = 0;

export const createCondition = (): RuleCondition => ({
    id: `condition-${conditionCounter++}`,
    condition_type: 'country',
    operator: 'equals',
    value: '',
    values: [''],
    time: {
        timezone: '',
        days: [],
        hours: {
            start: null,
            end: null,
        },
    },
});

export const createRule = (priority: number): Rule => ({
    id: `rule-${ruleCounter++}`,
    priority,
    destination_url: '',
    enabled: true,
    conditions: [createCondition()],
});
