import React, { Component } from "react";
import logo from "./logo.svg";
import "bootstrap/dist/css/bootstrap.css";
import "./App.css";

import CommentList from "./components/CommentList";
import CommentForm from "./components/CommentForm";

class App extends Component {
  constructor(props) {
    super(props);

    this.state = {
      comments: [],
      loading: false
    };

    this.addComment = this.addComment.bind(this);
  }

  componentDidMount() {
    // loading
    this.setState({ loading: true });

    // get all the comments
    fetch("http://localhost:7777")
      .then(res => res.json())
      .then(res => {
        this.setState({
          comments: res,
          loading: false
        });
      })
      .catch(err => {
        this.setState({ loading: false });
      });
  }

  /**
   * Add new comment
   * @param {Object} comment
   */
  addComment(comment) {
    this.setState({
      loading: false,
      comments: [comment, ...this.state.comments]
    });
  }

  render() {
    const loadingSpin = this.state.loading ? "App-logo Spin" : "App-logo";
    return (
      <div className="App container bg-light shadow">
        <header className="App-header">
          <img src={logo} className={loadingSpin} alt="logo" />
          <h1 className="App-title">
            React Comments
            <span className="px-2" role="img" aria-label="Chat">
              💬
            </span>
          </h1>
          <p>
            Checkout the tutorial on{" "}
            <a className="text-light" href="https://qcode.in">
              QCode.in
            </a>
          </p>
        </header>

        <div className="row">
          <div className="col-4  pt-3 border-right">
            <h6>Say something about React</h6>
            <CommentForm addComment={this.addComment} />
          </div>
          <div className="col-8  pt-3 bg-white">
            <CommentList
              loading={this.state.loading}
              comments={this.state.comments}
            />
          </div>
        </div>
      </div>
    );
  }
}

export default App;
