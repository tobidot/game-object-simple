<x-layouts.app class="page home" title="Home">
    <div class="page__content">

        <h2>
            Welcome to the home page!
        </h2>
        <p>
            This is my personal website, where i publish my projects and provide some background.
            I try to provide most projects with a live demo, so you can try them out yourself in the browser.
        </p>
        <h2>
            Discover
        </h2>
        <p>
            Check out the <a href="{{route('projects')}}">Projects Archive</a>,
            maybe you find something interesting and get inspired.
            <br/><br/>
            Or read some of my <a href="{{route('pages')}}">Blog Posts</a>,
            where i write about my projects or other things that interest me.
        </p>
        <h2>
            About me
        </h2>
        <p>
            I am a software developer from Germany, programming since i was little.
            I started with Turbo Pascal, then moved on to C++.
            After school i went to university and got my bachelor's degree in computer science,
            where i got in contact with various other languages like Java and Haskell.
            <br/><br/>
            After university i started working as a software developer, where i got in contact with PHP and JavaScript.
            I am currently working as a full stack developer, as passion projects i mostly try little games or
            situation.
            Lately i am also following the AI-Hype and try to get into machine learning.
        </p>
    </div>
</x-layouts.app>

