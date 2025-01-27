# Symfony-NLP-Form

## Description
This is a Form created with Symfony that takes information about properties from Users' as Natural Language then extracts the useful information and queries a MYSQL database for matching properties.

## Project Setup

This project contains a Symfony application along with a Python environment managed by Miniconda. Follow the steps below to set up both the Python environment and the Symfony server.

## Prerequisites

Before you begin, make sure you have the following tools installed:

- [Git](https://git-scm.com/)
- [Miniconda](https://docs.conda.io/en/latest/miniconda.html)
- [PHP](https://www.php.net/downloads) (for Symfony)
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) (optional, for development purposes)

## Setting Up the Python Environment

### 1. Install Miniconda
If you haven't already installed Miniconda, download and install it from the official [Miniconda website](https://docs.conda.io/en/latest/miniconda.html).

### 2. Create and Activate the Python Environment
Navigate to the project directory where the `python` folder is located. The `python` folder contains the environment setup files. Run the following commands:

```bash
# Create a new conda environment from the environment.yml file (if provided)
conda env create -f python/environment.yml
```
## Setting Up PHP Symfony
### Installation

1. **Clone the Repository**  
   ```bash
   git clone https://github.com/OsamaZ-1/Symfony-NLP-Form.git
   cd Symfony-NLP-Form
   ```

2. **Install PHP Dependencies**  
    ```bash
    composer install
    composer require doctrine/orm
    composer require --dev phpunit/phpunit
    ```

3. **Setup Symfony Environment**
    Change the .env file path values to match your paths.
    Specifically, change the **DATABASE_URL** path to match your database path.

4. **Databse Setup**
    Copy the code from the **properties.sql**  file and run it on your MySQL DBMS or import it there to acquire the database.

## Run the Server
Run the following command from the project directory.
```bash
symfony server:start
```

