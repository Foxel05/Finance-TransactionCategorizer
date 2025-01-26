# Finance Transaction Categorizer

The **Finance Transaction Categorizer** is a lightweight tool designed to automatically categorize bank transactions using a **Naive Bayes Gaussian model**. It is part of a larger finance application and provides a user-friendly interface for predicting, training, and managing transaction categories. The model learns from user corrections and dynamically improves its accuracy over time.

---

## Features

- **Transaction Categorization**: Predicts the category of a transaction based on its description using a Naive Bayes Gaussian model.
- **User-Specific Categories**: Supports multiple users with personalized categories.
- **Training Interface**: Allows users to correct predictions and train the model for better accuracy.
- **Dynamic Learning**: The model adapts to new patterns in transactions as users provide feedback.
- **Transaction History**: Displays a history of transactions with editable categories.
- **Bulk Updates**: Enables users to update multiple transaction categories at once.
- **Retraining**: Users can retrain the model periodically to ensure it stays accurate.
- **Probabilities Display**: Shows the probabilities of all possible categories for a given transaction.

---

## Technologies Used

- **Frontend**:
  - HTML, Tailwind CSS, Flowbite
  - JavaScript for dynamic interactions and API calls
- **Backend**:
  - PHP for server-side logic
  - [Niiknow/Bayes](https://github.com/niiknow/bayes) for the Naive Bayes classifier
- **Data Storage**:
  - JSON files for storing transactions and model states

---

## How It Works

The categorizer uses a **Naive Bayes Gaussian model** to classify transactions. Here's a high-level overview of the process:

1. **Input**: The user provides a transaction description (e.g., "Amazon purchase 123").
2. **Preprocessing**: The description is cleaned by removing irrelevant information like dates, amounts, and special characters.
3. **Prediction**: The model calculates the probability of the description belonging to each category and selects the most likely one.
4. **Training**: If the prediction is incorrect, the user provides the correct category, and the model is retrained with the new data.
5. **Retraining**: The model can be retrained periodically using the entire transaction history to improve accuracy.

---

## Installation

### Prerequisites

1. **PHP**: Ensure PHP is installed on your server or local machine.
2. **Composer**: Install Composer to manage PHP dependencies.

### Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/finance-TransactionCategorizer.git
   cd finance-TransactionCategorizer
   ```
2. **Install Dependencies**:
   Install the required PHP package (niiknow/bayes) using Composer:
   ```bash
   composer install
   ```
3. **Set Up the Data Directory**:
   Ensure the data.json file and models directory are writable by the web server:
   ```bash
   touch data.json
   mkdir models
   chmod 777 data.json models
   ```
4. **Run the Application**:
   If using a local server, start a PHP development server:
     ```bash
     php -S localhost:8000
     ```
   Open the application in your browser:
     ```bash
     http://localhost:8000
     ```

## Usage

1. Predict a Transaction Category
   Enter a transaction description in the input field.
   Click Predict Category to see the predicted category and probabilities.
2. Train the Model
    If the predicted category is incorrect, provide the correct category in the Correct Category field.
    Click Train Model to update the model with the new data.
3. View and Edit Transaction History
    The Transaction History table displays all transactions.
    Edit categories directly in the table and save changes using the Save All Changes button.
4. Retrain the Model
    Click Retrain Model to retrain the model using the updated transaction history.
5. Switch Users
    Use the Select User dropdown to switch between different users and their respective transaction data.

## Files

```
finance-TransactionCategorizer/
├── index.html              # Frontend HTML file
├── backend.php             # Backend PHP logic
├── data.json               # JSON file for storing transactions and categories
├── models/                 # Directory for storing user-specific model states
├── vendor/                 # Composer dependencies (generated after installation)
├── README.md               # This file
└── composer.json           # Composer configuration file
```

--- 

## Customization

### Adding New Users
To add a new user, simply add an option to the <select> element in index.html:
```
<option value="user3">User 3</option>
```

### Customizing Categories
Categories are dynamically added based on user input. To predefine categories, modify the initializeUser method in backend.php.

### Modifying the Model
The Naive Bayes classifier can be customized by adjusting the tokenizer or adding additional preprocessing steps in the getCustomTokenizer method.

---

## Contribution

Contributions are welcome! If you'd like to contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Commit your changes and push to your fork.
4. Submit a pull request with a detailed description of your changes.
