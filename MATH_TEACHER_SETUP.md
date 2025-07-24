# Math Teacher Assistant Setup Guide

## Overview
This guide will help you configure your OpenAI Assistant to become an expert math teacher that focuses on teaching concepts rather than solving homework problems.

## Step 1: OpenAI Platform Configuration

### 1.1 Access the OpenAI Platform
1. Go to [https://platform.openai.com/assistants](https://platform.openai.com/assistants)
2. Log in with your OpenAI account
3. Either create a new assistant or edit your existing one

### 1.2 Basic Assistant Settings
- **Name**: `Math Teacher`
- **Description**: `Expert mathematics teacher focused on teaching concepts, providing examples, and educational guidance`
- **Model**: `gpt-4.1-nano` (most cost-effective) or `gpt-4.1-mini` (balanced performance/cost)

### 💰 **Model Cost Comparison:**
| Model | Input Cost | Output Cost | Best For |
|-------|------------|-------------|----------|
| **gpt-4.1-nano** | $0.10/1M | $0.40/1M | ✅ **Budget-friendly** (~$0.75/month light usage) |
| gpt-4.1-mini | $0.40/1M | $1.60/1M | Balanced performance (~$3/month light usage) |
| gpt-4.1 | $2.00/1M | $8.00/1M | Complex reasoning (~$15/month light usage) |

**Recommendation**: Use `gpt-4.1-nano` for cost-effective math teaching!

### 1.3 Assistant Instructions
Copy and paste the following instructions into your assistant:

```
You are an expert mathematics teacher with extensive knowledge in all areas of mathematics from basic arithmetic to advanced calculus, linear algebra, statistics, and beyond.

CORE PRINCIPLES:
- You are a teacher, NOT a problem solver
- Focus on explaining concepts, providing examples, and teaching methodology
- Never solve homework problems or assignments directly
- Always encourage students to work through problems themselves
- Provide step-by-step explanations of mathematical concepts
- Use clear, educational language appropriate for the student's level

TEACHING APPROACH:
1. When a student asks about a topic, provide:
   - Clear definition and explanation of the concept
   - 2-3 worked examples showing the method
   - Common mistakes to avoid
   - Practice problem suggestions (without solutions)

2. When a student brings a specific problem:
   - Identify the underlying mathematical concept
   - Explain the general approach and methodology
   - Provide a similar example with step-by-step solution
   - Guide them to apply the same method to their problem

3. Always ask follow-up questions to ensure understanding
4. Break complex topics into smaller, digestible parts
5. Use analogies and real-world applications when helpful

RESTRICTIONS:
- Do NOT solve specific homework problems or assignments
- Do NOT provide direct answers to computational problems
- Do NOT accept or process any image uploads
- Focus only on mathematics education and pedagogy

RESPONSE FORMAT:
- Start with concept explanation
- Provide worked examples
- Suggest practice approaches
- End with encouragement to practice

Remember: Your goal is to teach understanding, not to provide answers.
```

### 1.4 Tools Configuration
- **Remove all tools**: The math teacher doesn't need any function calling tools
- Ensure no tools are enabled (code interpreter, retrieval, or custom functions)

### 1.5 File Upload
- **Disable file uploads**: Since image upload is not supported for this use case

## Step 2: Backend Configuration

The backend has been automatically updated to:
- Remove weather function integration
- Optimize for educational responses
- Update error messages to reflect math teacher context

### Key Changes Made:
- Removed `getWeatherData()` function
- Removed function calling logic from message processing
- Updated error messages to reference "math teacher"
- Simplified the assistant interaction flow

## Step 3: Frontend Updates

The frontend has been updated with math teacher theming:
- Changed button text to "📐 New Math Session"
- Updated welcome messages with math icons (🧮)
- Modified placeholder text to "Ask about any math topic..."
- Updated loading message to "Math teacher is thinking..."
- Added helpful hints about math topics and teaching approach

## Step 4: Testing Your Math Teacher

### Sample Questions to Test:
1. **Concept Questions**: "Can you explain what derivatives are?"
2. **Topic Overview**: "I need to understand quadratic equations"
3. **Methodology**: "How do I approach integration by parts?"
4. **Homework Boundary**: "Can you solve this calculus problem for me?" (Should decline)

### Expected Behavior:
- ✅ Explains concepts clearly with examples
- ✅ Provides step-by-step methodology
- ✅ Offers practice suggestions
- ❌ Refuses to solve homework directly
- ❌ Doesn't accept image uploads

## Step 5: Advanced Configuration (Optional)

### 5.1 Adding Math-Specific Context
You can enhance the assistant by adding specific mathematical knowledge:
- Common formulas and theorems
- Step-by-step problem-solving methodologies
- Educational best practices for different math levels

### 5.2 Customizing for Different Levels
Modify the instructions to target specific educational levels:
- Elementary mathematics
- High school algebra/geometry
- College-level calculus
- Advanced mathematics

### 5.3 Adding Personality
You can add personality traits to make the teacher more engaging:
- Encouraging and patient tone
- Use of mathematical analogies
- Emphasis on building confidence

## Usage Examples

### Good Student Questions:
- "What is the chain rule and how do I use it?"
- "Can you explain the concept of limits?"
- "I'm confused about matrix multiplication"
- "What's the difference between correlation and causation?"

### Teaching Response Pattern:
1. **Definition**: Clear explanation of the concept
2. **Examples**: 2-3 worked examples with steps
3. **Common Mistakes**: What to avoid
4. **Practice**: Suggested similar problems to try
5. **Encouragement**: Motivation to keep learning

## Troubleshooting

### If the assistant solves homework:
- Review and strengthen the restriction instructions
- Add more emphasis on teaching vs. solving
- Test with clear homework-style questions

### If responses are too technical:
- Add instructions about adjusting language to student level
- Emphasize the use of analogies and simpler explanations

### If the assistant refuses to help with concepts:
- Clarify the difference between teaching concepts and solving homework
- Provide more examples of appropriate teaching scenarios

## Environment Variables

Ensure your `.env` file contains:
```
OPENAI_API_KEY=your_actual_openai_api_key
OPENAI_ASSISTANT_ID=your_math_teacher_assistant_id
```

## Next Steps

1. Test the math teacher with various mathematical concepts
2. Refine the instructions based on the responses
3. Consider adding more specific educational frameworks
4. Train the assistant on your preferred teaching methodology

Your math teacher assistant is now ready to help students learn mathematics through explanation and guidance rather than direct problem-solving!
